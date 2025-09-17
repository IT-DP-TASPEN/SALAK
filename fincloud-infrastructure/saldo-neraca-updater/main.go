package main

import (
	"bufio"
	"database/sql"
	"encoding/json"
	"flag"
	"fmt"
	"net/http"
	"net/url"
	"os"
	"strings"
	"sync"
	"time"

	"github.com/joho/godotenv"

	_ "github.com/go-sql-driver/mysql"
)

const (
	batchSize  = 200
	maxRetries = 3
	baseURL    = "http://172.22.80.24/fincloud-taspen"
	userAgent  = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:142.0) Gecko/20100101 Firefox/142.0"
)

type LoginResponse struct {
	Data struct {
		Result struct {
			IdleTimeout  int64  `json:"idletimeout"`
			IdleWarning  int64  `json:"idlewarning"`
			LocationID   string `json:"locationid"`
			LocationName string `json:"locationname"`
			RoleID       string `json:"roleid"`
			RoleName     string `json:"rolename"`
			SessionID    string `json:"sessionid"`
		} `json:"result"`
	} `json:"data"`
	Error *struct {
		System string `json:"system"`
		// User   string `json:"user"`
	} `json:"error,omitempty"`
	Status string `json:"status"`
}

type SaldoNeraca struct {
	Data struct {
		Result []struct {
			Cabang       string `json:"cabang"`
			NoAkun       string `json:"noakun"`
			NamaAkun     string `json:"namaakun"`
			SaldoAwal    string `json:"saldoawal"`
			MutasiDebit  string `json:"mutasidebit"`
			MutasiKredit string `json:"mutasikredit"`
			SaldoAkhir   string `json:"saldoakhir"`
		} `json:"result"`
		PageSize   int64  `json:"pageSize"`
		PageNumber int64  `json:"pageNumber"`
		RowCount   string `json:"rowCount"`
	} `json:"data"`
	Status string `json:"status"`
}

func main() {
	err := godotenv.Load()
	if err != nil {
		fmt.Fprintf(os.Stderr, "Error loading .env file: %v\n", err)
		return
	}

	db, err := connectDB()
	if err != nil {
		fmt.Fprintf(os.Stderr, "Error connecting to database: %v\n", err)
		return
	}
	defer db.Close()

	dateStr := flag.String("date", "", "Date for saldo neraca in YYYY-MM-DD format (default: today)")
	flag.Parse()

	if *dateStr != "" {
		if _, err := time.Parse("2006-01-02", *dateStr); err != nil {
			fmt.Fprintf(os.Stderr, "Invalid date format: %v\n", err)
			return
		}
	} else {
		yesterday := time.Now().AddDate(0, 0, -1)
		*dateStr = yesterday.Format("2006-01-02")
	}

	date, err := time.Parse("2006-01-02", *dateStr)
	if err != nil {
		fmt.Fprintf(os.Stderr, "Error parsing date: %v\n", err)
		return
	}

	username, password, err := getCredentials()
	if err != nil {
		fmt.Fprintf(os.Stderr, "Error getting credentials: %v\n", err)
		return
	}

	loginResp, err := login(username, password)
	if err != nil {
		fmt.Fprintf(os.Stderr, "Error during login: %v\n", err)
		return
	}

	fmt.Printf(
		"Logged in as %s (Role: %s - %s)\n",
		username,
		loginResp.Data.Result.LocationName,
		loginResp.Data.Result.RoleName,
	)

	kcList := make([]string, 8)
	for i := range kcList {
		kcList[i] = fmt.Sprintf("%03d", i+1)
	}

	type saldoResult struct {
		branch      string
		saldoNeraca SaldoNeraca
		err         error
	}

	resCh := make(chan saldoResult, len(kcList))
	wg := sync.WaitGroup{}

	fmt.Printf("Fetching saldo neraca for date: %s\n", date.Format("2006-01-02"))

	for _, kc := range kcList {
		wg.Go(func() {
			saldo, err := fetchSaldoNeraca(loginResp.Data.Result.SessionID, kc, date)
			if err != nil {
				for attempt := 1; attempt <= maxRetries; attempt++ {
					fmt.Printf("Retrying fetch for branch %s (attempt %d/%d)\n", kc, attempt, maxRetries)
					saldo, err = fetchSaldoNeraca(loginResp.Data.Result.SessionID, kc, date)
					if err == nil {
						break
					}
					time.Sleep(2 * time.Second) // Backoff before retrying
				}
			}

			if err != nil {
				resCh <- saldoResult{branch: kc, err: err}
				return
			}

			resCh <- saldoResult{branch: kc, saldoNeraca: saldo}
		})
	}

	go func() {
		wg.Wait()
		close(resCh)
	}()

	for res := range resCh {
		if res.err != nil {
			fmt.Fprintf(
				os.Stderr,
				"Error fetching saldo neraca for branch %s: %v\n",
				res.branch,
				res.err,
			)
			continue
		}

		branch := res.branch
		if len(res.saldoNeraca.Data.Result) > 0 &&
			res.saldoNeraca.Data.Result[0].Cabang != "" {
			branch = res.saldoNeraca.Data.Result[0].Cabang
		}

		fmt.Printf("Branch %s - Retrieved %d records\n", branch, len(res.saldoNeraca.Data.Result))
		err = insertOrUpdateSaldo(db, date.Format("2006-01-02"), res.branch, res.saldoNeraca)
		if err != nil {
			fmt.Fprintf(
				os.Stderr,
				"Error inserting/updating saldo neraca for branch %s: %v\n",
				branch,
				err,
			)
			continue
		}
		fmt.Printf("Branch %s - Successfully inserted/updated records\n", branch)
	}
}

func connectDB() (*sql.DB, error) {
	db, err := sql.Open("mysql", os.Getenv("DB_SOURCE"))
	if err != nil {
		return nil, err
	}

	if err = db.Ping(); err != nil {
		return nil, err
	}

	// Pool tuning for higher throughput
	db.SetMaxOpenConns(20)
	db.SetMaxIdleConns(10)
	db.SetConnMaxLifetime(30 * time.Minute)

	return db, nil
}

func insertOrUpdateSaldo(db *sql.DB, date, branch string, saldo SaldoNeraca) error {
	sanitize := func(s string) string {
		s = strings.ReplaceAll(s, ",", "")
		s = strings.ReplaceAll(s, "<", "")
		s = strings.ReplaceAll(s, ">", "")
		return strings.TrimSpace(s)
	}

	if len(saldo.Data.Result) == 0 {
		return nil
	}

	now := time.Now()

	// Pre-sanitize and stage rows
	type row struct {
		Cabang, Tanggal, NoAkun, NamaAkun, SaldoAwal, MutasiDebit, MutasiKredit, SaldoAkhir string
		CreatedAt, UpdatedAt                                                                time.Time
	}
	rows := make([]row, 0, len(saldo.Data.Result))
	for _, r := range saldo.Data.Result {
		rows = append(rows, row{
			Cabang:       branch,
			Tanggal:      date,
			NoAkun:       r.NoAkun,
			NamaAkun:     r.NamaAkun,
			SaldoAwal:    sanitize(r.SaldoAwal),
			MutasiDebit:  sanitize(r.MutasiDebit),
			MutasiKredit: sanitize(r.MutasiKredit),
			SaldoAkhir:   sanitize(r.SaldoAkhir),
			CreatedAt:    now,
			UpdatedAt:    now,
		})
	}

	for i := 0; i < len(rows); i += batchSize {
		end := min(i+batchSize, len(rows))

		var sb strings.Builder
		sb.WriteString(`INSERT INTO `)
		sb.WriteString(os.Getenv("TABLE_SOURCE"))
		sb.WriteString(`
			(cabang, tanggal, noakun, namaakun, saldoawal, mutasidebit, mutasikredit, saldoakhir, created_at, updated_at)
			VALUES `,
		)

		placeholders := make([]string, 0, end-i)
		args := make([]any, 0, (end-i)*10)

		for _, r := range rows[i:end] {
			placeholders = append(placeholders, "(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")
			args = append(args,
				r.Cabang, r.Tanggal, r.NoAkun, r.NamaAkun,
				r.SaldoAwal, r.MutasiDebit, r.MutasiKredit, r.SaldoAkhir,
				r.CreatedAt, r.UpdatedAt,
			)
		}

		sb.WriteString(strings.Join(placeholders, ","))
		sb.WriteString(`
            ON DUPLICATE KEY UPDATE
                namaakun = VALUES(namaakun),
                saldoawal = VALUES(saldoawal),
                mutasidebit = VALUES(mutasidebit),
                mutasikredit = VALUES(mutasikredit),
                saldoakhir = VALUES(saldoakhir),
                updated_at = VALUES(updated_at)`)

		tx, err := db.Begin()
		if err != nil {
			return fmt.Errorf("begin tx: %w", err)
		}

		if _, err := tx.Exec(sb.String(), args...); err != nil {
			_ = tx.Rollback()
			return fmt.Errorf("bulk upsert: %w", err)
		}

		if err := tx.Commit(); err != nil {
			return fmt.Errorf("commit tx: %w", err)
		}
	}

	return nil
}

func getCredentials() (string, string, error) {
	username := os.Getenv("FINCLOUD_USERNAME")
	if username == "" {
		input, err := askInput("Enter username: ")
		if err != nil {
			fmt.Fprintf(os.Stderr, "Error reading username: %v\n", err)
			return "", "", err
		}
		username = strings.TrimSpace(input)
	}

	password := os.Getenv("FINCLOUD_PASSWORD")
	if password == "" {
		input, err := askInput("Enter password: ")
		if err != nil {
			fmt.Fprintf(os.Stderr, "Error reading password: %v\n", err)
			return "", "", err
		}
		password = strings.TrimSpace(input)
	}

	return username, password, nil
}

func askInput(prompt string) (string, error) {
	fmt.Print(prompt)
	reader := bufio.NewReader(os.Stdin)
	input, err := reader.ReadString('\n')
	if err != nil {
		return "", err
	}
	return input, nil
}

func login(username, password string) (LoginResponse, error) {
	form := url.Values{}
	form.Add("locationid", "001") // Kantor Pusat Operasional
	form.Add("roleid", "R-0004")  // Back Office
	form.Add("username", username)
	form.Add("pwd", password)

	req, err := http.NewRequest("POST", baseURL+"/admin/access/login", strings.NewReader(form.Encode()))
	if err != nil {
		return LoginResponse{}, err
	}
	req.Header.Set("Content-Type", "application/x-www-form-urlencoded")
	req.Header.Set("User-Agent", userAgent)

	client := &http.Client{}
	resp, err := client.Do(req)
	if err != nil {
		return LoginResponse{}, err
	}
	defer resp.Body.Close()

	if resp.StatusCode != http.StatusOK {
		return LoginResponse{}, fmt.Errorf("login failed: %s", resp.Status)
	}

	var result LoginResponse
	err = json.NewDecoder(resp.Body).Decode(&result)
	if err != nil {
		return LoginResponse{}, err
	}

	if result.Status != "ok" {
		if result.Error != nil {
			return LoginResponse{}, fmt.Errorf("login error: %s", result.Error.System)
		}
		return LoginResponse{}, fmt.Errorf("login failed with unknown error")
	}

	return result, nil
}

func fetchSaldoNeraca(sessionId, branchOffice string, date time.Time) (SaldoNeraca, error) {
	req, err := http.NewRequest(
		"GET",
		baseURL+"/bukuBesar/laporan/neracasaldo/cari",
		nil,
	)
	if err != nil {
		return SaldoNeraca{}, err
	}

	q := req.URL.Query()
	q.Add("cabang", branchOffice)
	q.Add("pagenumber", "0")
	q.Add("pagesize", "50000")
	q.Add("rowcount", "0")
	q.Add("tanggal", date.Format("2006-01-02"))
	q.Add("tgl", time.Now().Format("2006-01-02"))
	req.URL.RawQuery = q.Encode()

	req.Header.Set("User-Agent", userAgent)
	req.Header.Set("sessionid", sessionId)

	client := &http.Client{}
	resp, err := client.Do(req)
	if err != nil {
		return SaldoNeraca{}, err
	}
	defer resp.Body.Close()

	if resp.StatusCode != http.StatusOK {
		return SaldoNeraca{}, fmt.Errorf("failed to fetch saldo neraca: %s", resp.Status)
	}

	var result SaldoNeraca
	err = json.NewDecoder(resp.Body).Decode(&result)
	if err != nil {
		return SaldoNeraca{}, err
	}

	if result.Status != "ok" {
		return SaldoNeraca{}, fmt.Errorf("failed to fetch saldo neraca with unknown error")
	}

	return result, nil
}
