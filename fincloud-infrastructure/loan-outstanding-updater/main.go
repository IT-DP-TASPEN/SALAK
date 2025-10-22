package main

import (
	"bufio"
	"crypto/tls"
	"database/sql"
	"encoding/json"
	"flag"
	"fmt"
	"net/http"
	"net/url"
	"os"
	"strings"
	"time"

	"github.com/joho/godotenv"

	_ "github.com/go-sql-driver/mysql"
)

const (
	batchSize = 200
	baseURL   = "https://172.20.57.7/fincloud-taspen-web"
	userAgent = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:142.0) Gecko/20100101 Firefox/142.0"
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

type BranchOffice string

func (p *BranchOffice) UnmarshalText(text []byte) error {
	s := string(text)
	i := strings.IndexByte(s, '-')
	if i <= 0 { // no dash or empty BranchOffice
		return fmt.Errorf("invalid branch code format value: %q", s)
	}
	*p = BranchOffice(s[:i])
	return nil
}

type LoanOutstanding struct {
	Data struct {
		Result []struct {
			DateParams       string       `json:"Date Params"`
			BranchCode       BranchOffice `json:"Branch Code"`
			ProductID        string       `json:"Product ID"`
			LoanAccNo        string       `json:"Loan Acc No"`
			CustomerName     string       `json:"Customer Name"`
			CIFNo            string       `json:"CIF No"`
			LoanAltNo        string       `json:"Loan Alt No"`
			LoanAgreementNo  string       `json:"Loan Agreement No"`
			StartDate        string       `json:"Start Date"`
			EndDate          string       `json:"End Date"`
			InterestRate     float64      `json:"Interest Rate"`
			InstallmentLoans float64      `json:"Installment Loans"`
			BICollectability uint8        `json:"BI Collectability"`
			DayPastDue       int64        `json:"Day Past Due"`
			Currency         string       `json:"Currency"`
			LoanPrincipal    float64      `json:"Loan Principal"`
			LoanOutstanding  float64      `json:"Loan Outstanding"`
			PrincipalArrears float64      `json:"Principal Arrears"`
			InterestArrears  float64      `json:"Interest Arrears"`
			PenaltyArrears   float64      `json:"Penalty Arrears"`
			AccrueInterest   float64      `json:"Accrue Interest"`
			MarketingCode    interface{}  `json:"Marketing Code"`
			OverRepayment    float64      `json:"Over-Repayment"`
		} `json:"result"`
	} `json:"data"`
	Status string `json:"status"`
}

func main() {
	err := godotenv.Load()
	if err != nil {
		fmt.Fprintf(os.Stderr, "Error loading .env file: %v\n", err)
		os.Exit(1)
	}

	db, err := connectDB()
	if err != nil {
		fmt.Fprintf(os.Stderr, "Error connecting to database: %v\n", err)
		os.Exit(1)
	}
	defer db.Close()

	dateStr := flag.String("date", "", "Date for saldo neraca in YYYY-MM-DD format (default: today)")
	flag.Parse()

	if *dateStr != "" {
		if _, err := time.Parse("2006-01-02", *dateStr); err != nil {
			fmt.Fprintf(os.Stderr, "Invalid date format: %v\n", err)
			os.Exit(1)
		}
	} else {
		yesterday := time.Now().AddDate(0, 0, -1)
		*dateStr = yesterday.Format("2006-01-02")
	}

	_, err = time.Parse("2006-01-02", *dateStr)
	if err != nil {
		fmt.Fprintf(os.Stderr, "Error parsing date: %v\n", err)
		os.Exit(1)
	}

	username, password, err := getCredentials()
	if err != nil {
		fmt.Fprintf(os.Stderr, "Error getting credentials: %v\n", err)
		os.Exit(1)
	}

	loginResp, err := login(username, password)
	if err != nil {
		fmt.Fprintf(os.Stderr, "Error during login: %v\n", err)
		os.Exit(1)
	}

	fmt.Printf(
		"Logged in as %s (Role: %s - %s)\n",
		username,
		loginResp.Data.Result.LocationName,
		loginResp.Data.Result.RoleName,
	)

	loanOutstandings, err := fetchLoanOutstandings(loginResp.Data.Result.SessionID, *dateStr)
	if err != nil {
		fmt.Fprintf(os.Stderr, "Error when fetching loan outstandings: %v\n", err)
		os.Exit(1)
	}

	fmt.Printf("Fetched %d loan outstandings for %s\n", len(loanOutstandings.Data.Result), *dateStr)

	// truncate existing data
	_, err = db.Exec(fmt.Sprintf("TRUNCATE TABLE %s", os.Getenv("TABLE_SOURCE")))
	if err != nil {
		fmt.Fprintf(os.Stderr, "Error truncating table: %v\n", err)
		os.Exit(1)
	}
	fmt.Println("Truncated existing loan outstandings data")

	// chunk insert to avoid too large query
	for i := 0; i < len(loanOutstandings.Data.Result); i += batchSize {
		end := min(i+batchSize, len(loanOutstandings.Data.Result))
		batch := loanOutstandings.Data.Result[i:end]

		tx, err := db.Begin()
		if err != nil {
			fmt.Fprintf(os.Stderr, "Error starting transaction: %v\n", err)
			os.Exit(1)
		}

		stmt, err := tx.Prepare(fmt.Sprintf(
			`INSERT INTO %s
			(
				loan_date_params,
				loan_branch_office,
				loan_product,
				loan_account,
				loan_customer,
				loan_cif,
				loan_alt_account,
				loan_agreement_no,
				loan_start_date,
				loan_end_date,
				loan_interest_rate,
				loan_installment_loans,
				loan_bi_collectability,
				loan_days_past_due,
				loan_currency,
				loan_principal,
				loan_outstanding,
				loan_principal_arrears,
				loan_interest_arrears,
				loan_penalty_arrears,
				loan_accrue_interest,
				loan_marketing_code,
				loan_over_repayment,
				created_at,
				updated_at
			) VALUES (
				?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()
			)
			`,
			os.Getenv("TABLE_SOURCE"),
		))
		if err != nil {
			fmt.Fprintf(os.Stderr, "Error preparing statement: %v\n", err)
			os.Exit(1)
		}

		for _, lo := range batch {
			_, err = stmt.Exec(
				lo.DateParams,
				lo.BranchCode,
				lo.ProductID,
				lo.LoanAccNo,
				lo.CustomerName,
				lo.CIFNo,
				lo.LoanAltNo,
				lo.LoanAgreementNo,
				lo.StartDate,
				lo.EndDate,
				lo.InterestRate,
				lo.InstallmentLoans,
				lo.BICollectability,
				lo.DayPastDue,
				lo.Currency,
				lo.LoanPrincipal,
				lo.LoanOutstanding,
				lo.PrincipalArrears,
				lo.InterestArrears,
				lo.PenaltyArrears,
				lo.AccrueInterest,
				lo.MarketingCode,
				lo.OverRepayment,
			)
			if err != nil {
				fmt.Fprintf(os.Stderr, "Error executing statement: %v\n", err)
				os.Exit(1)
			}
		}

		err = stmt.Close()
		if err != nil {
			fmt.Fprintf(os.Stderr, "Error closing statement: %v\n", err)
			os.Exit(1)
		}

		err = tx.Commit()
		if err != nil {
			fmt.Fprintf(os.Stderr, "Error committing transaction: %v\n", err)
			os.Exit(1)
		}

		fmt.Printf("Inserted batch of %d loan outstandings\n", len(batch))
	}

	fmt.Println("Loan outstanding update completed successfully.")
}

func fetchLoanOutstandings(sessionId, dateStr string) (*LoanOutstanding, error) {
	req, err := http.NewRequest("GET", baseURL+"/system/laporanUmum/pembuatan/detaillaporan", nil)
	if err != nil {
		return nil, err
	}

	params := []string{"", dateStr}
	p, err := json.Marshal(params)
	if err != nil {
		return nil, err
	}

	q := req.URL.Query()
	q.Add("nm", "Loan Outstanding Details Report")
	q.Add("p", string(p))
	req.URL.RawQuery = q.Encode()

	req.Header.Set("User-Agent", userAgent)
	req.Header.Set("sessionid", sessionId)

	tr := &http.Transport{
		TLSClientConfig: &tls.Config{InsecureSkipVerify: true}, // Disable certificate validation
	}
	client := &http.Client{Transport: tr}
	resp, err := client.Do(req)
	if err != nil {
		return nil, err
	}
	defer resp.Body.Close()

	if resp.StatusCode != http.StatusOK {
		return nil, fmt.Errorf("failed to fetch loan outstandings: %s", resp.Status)
	}

	var result LoanOutstanding
	err = json.NewDecoder(resp.Body).Decode(&result)
	if err != nil {
		return nil, err
	}

	if result.Status != "ok" {
		return nil, fmt.Errorf("failed to fetch loan outstandings with unknown error")
	}

	return &result, nil
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
	form.Add("locationid", "000") // Headquarter
	form.Add("roleid", "R-0041")  // Reporting
	form.Add("username", username)
	form.Add("pwd", password)

	req, err := http.NewRequest("POST", baseURL+"/admin/access/login", strings.NewReader(form.Encode()))
	if err != nil {
		return LoginResponse{}, err
	}
	req.Header.Set("Content-Type", "application/x-www-form-urlencoded;charset=utf-8")
	req.Header.Set("User-Agent", userAgent)

	tr := &http.Transport{
		TLSClientConfig: &tls.Config{InsecureSkipVerify: true}, // Disable certificate validation
	}
	client := &http.Client{Transport: tr}
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
