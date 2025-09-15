package main

import (
	"bufio"
	"encoding/json"
	"fmt"
	"net/http"
	"net/url"
	"os"
	"reflect"
	"strings"
	"sync"
	"sync/atomic"
	"time"

	"github.com/joho/godotenv"
)

const (
	batchSize = 200
	baseURL   = "http://172.22.80.24/fincloud-taspen"
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

type DataKredit struct {
	Data struct {
		Result []struct {
			ID                  string `json:"id"`
			ProdukId            string `json:"produkid"`
			ProdukJenisPinjaman string `json:"produk_jenispinjaman"`
			NoCIF               string `json:"nocif"`
			NoPK                string `json:"nopk"`
			NamaNasabah         string `json:"namanasabah"`
			NamaAlias           string `json:"namaalias"`
			StatusDokumen       string `json:"status_dokumen"`
			Currency            string `json:"currency"`
			TanggalWO           string `json:"tanggalwo"`
			JumlahPokokPinjaman string `json:"jmlpokok_pinjaman"`
		} `json:"result"`
	} `json:"data"`
	Status string `json:"status"`
}

func main() {
	err := godotenv.Load()
	if err != nil {
		fmt.Fprintf(os.Stderr, "Error loading .env file: %v\n", err)
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

	err = inquiryLoans(loginResp.Data.Result.SessionID)
	if err != nil {
		fmt.Fprintf(os.Stderr, "Error during loan inquiry: %v\n", err)
		return
	}
}

func inquiryLoans(sessionId string) error {
	// http://172.22.80.24/fincloud-taspen/pinjaman/inquiry/rekening/cari?jenispinjaman=&pagecount=NaN&pagenumber=0&pagesize=50&status=Aktif
	req, err := http.NewRequest("GET", baseURL+"/pinjaman/inquiry/rekening/cari", nil)
	if err != nil {
		return err
	}
	q := req.URL.Query()
	q.Add("jenispinjaman", "")
	q.Add("pagecount", "NaN")
	q.Add("pagenumber", "0")
	q.Add("pagesize", "50")
	q.Add("status", "Aktif")
	req.URL.RawQuery = q.Encode()

	req.Header.Set("User-Agent", userAgent)
	req.Header.Set("sessionid", sessionId)

	client := &http.Client{}
	resp, err := client.Do(req)
	if err != nil {
		return err
	}
	defer resp.Body.Close()

	if resp.StatusCode != http.StatusOK {
		return fmt.Errorf("failed to inquiry loans: %s", resp.Status)
	}

	var result DataKredit
	err = json.NewDecoder(resp.Body).Decode(&result)
	if err != nil {
		return err
	}

	if result.Status != "ok" {
		return fmt.Errorf("inquiry loans failed with status: %s", result.Status)
	}

	wg := sync.WaitGroup{}
	errCount := atomic.Int32{}
	sem := make(chan struct{}, 120)
	defer close(sem)

	start := time.Now()
	for i, loan := range result.Data.Result {
		sem <- struct{}{}
		wg.Go(func() {
			defer func() { <-sem }()
			// fmt.Printf("[%v] Fetching details for loan ID: %s\n", i, loan.ID)
			err := fetchDetails(sessionId, loan.ID)
			if err != nil {
				errCount.Add(1)
				fmt.Fprintf(os.Stderr, "Error fetching details for loan ID %s: %v\n", loan.ID, err)
			}
		})
		// Throttle to avoid overwhelming the server
		if (i+1)%batchSize == 0 {
			wg.Wait()
		}
	}

	// Wait for all goroutines to finish
	wg.Wait()
	elapsed := time.Since(start)
	fmt.Printf("Fetched details for %d (%d errored) loans in %s\n", len(result.Data.Result), errCount.Load(), elapsed)

	return nil
}

func fetchDetails(sessionId, loanId string) error {
	// http://172.22.80.24/fincloud-taspen/pinjaman/inquiry/rekening/pinjaman?id=300001000000001
	req, err := http.NewRequest("GET", baseURL+"/pinjaman/inquiry/rekening/pinjaman", nil)
	if err != nil {
		return err
	}
	q := req.URL.Query()
	q.Add("id", loanId)
	req.URL.RawQuery = q.Encode()

	req.Header.Set("User-Agent", userAgent)
	req.Header.Set("sessionid", sessionId)

	client := &http.Client{}
	resp, err := client.Do(req)
	if err != nil {
		return err
	}
	defer resp.Body.Close()

	if resp.StatusCode != http.StatusOK {
		return fmt.Errorf("failed to fetch loan details: %s", resp.Status)
	}

	var result KreditDetail
	err = json.NewDecoder(resp.Body).Decode(&result)
	if err != nil {
		return err
	}

	if result.Status != "ok" {
		return fmt.Errorf("fetch loan details failed with status: %s", result.Status)
	}

	// fmt.Println(result.Data.Result.TempatPenyimpanan)
	// loop through all fields and print the value if it's type is interface{}
	fields := result.Data.Result
	fmt.Println("Loan ID:", loanId)
	v := reflect.ValueOf(fields)
	typeOfS := v.Type()

	for i := 0; i < v.NumField(); i++ {
		field := v.Field(i)
		if field.Kind() == reflect.Interface && !field.IsNil() {
			fmt.Printf("  - %s: %v (type: %T)\n", typeOfS.Field(i).Name, field.Interface(), field.Interface())
		}
	}

	fmt.Println("----------------------------------------")

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
	form.Add("roleid", "R-0013")  // CREDIT
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

type KreditDetail struct {
	Data struct {
		Result struct {
			RestrukturNoAkadAkhir         interface{} `json:"restruktur_noakad_akhir"`
			RestrukturTanggalAkhirAkad    interface{} `json:"restruktur_tanggalakhirakad"`
			RestrukturTanggalAwal         interface{} `json:"restruktur_tanggalawal"`
			RestrukturTanggalAkhir        interface{} `json:"restruktur_tanggalakhir"`
			RestrukturCara                string      `json:"restruktur_cara"`
			RestrukturFrekuensi           string      `json:"restruktur_frekuensi"`
			TanggalMulaiTunggakan         string      `json:"tanggalmulaitunggakan"`
			Lokasi                        string      `json:"lokasi"`
			ID                            string      `json:"id"`
			NoPK                          string      `json:"nopk"`
			NamaNasabah                   string      `json:"namanasabah"`
			AliasNama                     string      `json:"aliasnama"`
			StatusRekening                string      `json:"statusrekening"`
			TglPencairan                  string      `json:"tgl_pencairan"`
			RecDibuatOleh                 string      `json:"rec_dibuat_oleh"`
			NoAlt                         string      `json:"noalt"`
			ProdukJenisPinjaman           string      `json:"produk_jenispinjaman"`
			ProdukId                      string      `json:"produkid"`
			IdProduk                      string      `json:"idproduk"`
			Currency                      string      `json:"currency"`
			PlafondLimit                  string      `json:"plafondlimit"`
			JumlahPokokPinjaman           string      `json:"jmlpokok_pinjaman"`
			JangkaWaktu                   string      `json:"jangkawaktu"`
			ProdukJenisAngsuran           string      `json:"produk_jenisangsuran"`
			TglAngsuran                   int64       `json:"tgl_angsuran"`
			SidSifatKredit2               string      `json:"sid_sifatkredit2"`
			SidJenisPenggunaan            string      `json:"sid_jenispenggunaan"`
			SidSumberDanaPelunasan        string      `json:"sid_sumberdanapelunasan"`
			SidGolonganKredit             string      `json:"sid_golongankredit"`
			SidOrientasiPenggunaan        string      `json:"sid_orientasipenggunaan"`
			SidSektorEkonomi              string      `json:"sid_sektorekonomi"`
			PejabatKredit                 interface{} `json:"pejabatkredit"`
			PejabatKreditDua              interface{} `json:"pejabatkreditdua"`
			SidSektorEkonomi2             string      `json:"sid_sektorekonomi2"`
			SidSifatKredit                string      `json:"sid_sifatkredit"`
			DataPenjamin                  string      `json:"datapenjamin"`
			MengetahuiSuamiIstri          string      `json:"mengetahuisuamiistri"`
			DPNama                        string      `json:"dp_nama,omitempty"`
			DPNoKtp                       string      `json:"dp_noktp,omitempty"`
			DPAlamat                      string      `json:"dp_alamat,omitempty"`
			DPTempatLahir                 string      `json:"dp_tempatlahir,omitempty"`
			DPTglLahir                    *Tgl        `json:"dp_tgllahir,omitempty"`
			DPJenisKelamin                string      `json:"dp_jeniskelamin,omitempty"`
			DPGolDarah                    string      `json:"dp_goldarah,omitempty"`
			DPAgama                       string      `json:"dp_agama,omitempty"`
			DPStatusPerkawinan            string      `json:"dp_statusperkawinan,omitempty"`
			DPPekerjaan                   string      `json:"dp_pekerjaan,omitempty"`
			DPNoCIF                       string      `json:"dp_nocif,omitempty"`
			DPHubungan                    string      `json:"dp_hubungan,omitempty"`
			DPKewarganegaraan             string      `json:"dp_kewarganegaraan,omitempty"`
			DPTempatTerbit                string      `json:"dp_tempatterbit,omitempty"`
			DPTglTerbit                   *Tgl        `json:"dp_tglterbit,omitempty"`
			DPBerlakuSeumurHidup          string      `json:"dp_berlakuseumurhidup,omitempty"`
			DPTglBerlakuSampai            *Tgl        `json:"dp_tglberlakusampai,omitempty"`
			SidJenisUsaha                 string      `json:"sid_jenisusaha"`
			BungaFlat                     int64       `json:"bungaflat"`
			NoPerjanjianKredit            string      `json:"noperjanjiankredit"`
			JournalId                     string      `json:"journalid"`
			PersenDendaTunggakan          int64       `json:"persendendatunggakan"`
			BungaBerjenjang               string      `json:"bungaberjenjang"`
			NoRekGabunganBnpl             string      `json:"norekgabungan_bnpl"`
			TglTutup                      *Tgl        `json:"tgl_tutup,omitempty"`
			Titipan                       int64       `json:"titipan"`
			Periode                       string      `json:"periode"`
			ProdukSukuBunga               float64     `json:"produk_sukubunga"`
			ProdukPerubahanSukuBunga      string      `json:"produk_perubahansukubunga"`
			TujuanKredit                  string      `json:"tujuankredit,omitempty"`
			TglTerakhirBayarPokokDanBunga Tgl         `json:"tglterakhir_bayarpokokdanbunga"`
			TglBayarPokokBungaBerikutnya  Tgl         `json:"tglbayarpokokbunga_berikutnya"`
			TglJatuhTempoTerakhir         Tgl         `json:"tgljtterakhir"`
			TglJatuhTempoBerikutnya       Tgl         `json:"tgljtberikutnya"`
			OutstandingPinjaman           string      `json:"outstandingpinjaman"`
			TunggakanPokok                string      `json:"tunggakanpokok"`
			Accrue                        string      `json:"accrue"`
			DecimalPoint                  int64       `json:"decimalpoint"`
			TunggakanBunga                string      `json:"tunggakanbunga"`
			DendaTunggakan                string      `json:"dendatunggakan"`
			Dpd                           int64       `json:"dpd"`
			KolekBI                       int64       `json:"kolekbi"`
			KolekBPR                      int64       `json:"kolekbpr"`
			UpdateKolekBI                 string      `json:"updatekolekbi"`
			TotalCollateralValue          int64       `json:"totalcollateralvalue"`
			TotalAssetValue               int64       `json:"totalassetvalue"`
			NoCIF                         string      `json:"nocif"`
			JenisNasabah                  string      `json:"jenisnasabah"`
			TglBukaCIF                    Tgl         `json:"tglbukacif"`
			StatusDokumen                 string      `json:"status_dokumen"`
			DataAlamatKtpAlamat1          string      `json:"dataalamat_ktp_alamat1"`
			DataAlamatKtpAlamat2          string      `json:"dataalamat_ktp_alamat2,omitempty"`
			DataAlamatKtpRt               string      `json:"dataalAmat_ktp_rt"`
			DataAlamatKtpRw               string      `json:"dataalAmat_ktp_rw"`
			DataAlamatKtpKelurahan        string      `json:"dataalAmat_ktp_kelurahan,omitempty"`
			DataAlamatKtpKecamatan        string      `json:"dataalAmat_ktp_kecamatan"`
			DataAlamatKtpKota             string      `json:"dataalAmat_ktp_kota,omitempty"`
			DataAlamatKtpPropinsi         string      `json:"dataalAmat_ktp_propinsi,omitempty"`
			DataAlamatKtpKodepos          string      `json:"dataalAmat_ktp_kodepos"`
			DataAlamatRumahNohp           string      `json:"dataalAmat_rumah_nohp"`
			NoRekTabPencairanPinjaman     string      `json:"norektab_pencairanpinjaman"`
			NoRekTabBayarAngsuran         string      `json:"norektab_bayarangsuran"`
			NoRekTabPencairanPinjaman2    string      `json:"norektab_pencairanpinjaman2"`
			NoRekTabBayarAngsuran2        string      `json:"norektab_bayarangsuran2"`
			JenisJaminan2                 string      `json:"jenisjaminan2,omitempty"`
			JenisJaminan                  string      `json:"jenisjaminan,omitempty"`
			TotalNilaiPasar               string      `json:"totalnilaipasar"`
			Terpakai                      int64       `json:"terpakai"`
			TotalNilaiJaminan             int64       `json:"totalnilaijaminan"`
			Jaminan                       string      `json:"jaminan"`
			JmlAgunan                     int64       `json:"jmlagunan"`
			TglHapusBuku                  *Tgl        `json:"tglhapusbuku,omitempty"`
			TotalHapusBuku                string      `json:"totalhapusbuku"`
			NilaiHapusBukuSaldoPinjaman   interface{} `json:"nilaihapusbuku_saldopinjaman"`
			NilaiHapusBukuBungaberjalan   interface{} `json:"nilaihapusbuku_bungaberjalan"`
			NilaiHapusBukuTunggakanbunga  interface{} `json:"nilaihapusbuku_tunggakanbunga"`
			NilaiHapusBukuTunggakandenda  interface{} `json:"nilaihapusbuku_tunggakandenda"`
			PpapBlnTerakhir               interface{} `json:"ppapblnterakhir"`
			PpapTglTerakhir               interface{} `json:"ppaptglterakhir"`
			Marketing                     string      `json:"marketing,omitempty"`
			DataCsNotes                   string      `json:"datacs_notes,omitempty"`
			AnalisKredit                  string      `json:"analiskredit,omitempty"`
			AnalisKreditNotes             string      `json:"analiskredit_notes,omitempty"`
			HTPokok                       string      `json:"ht_pokok"`
			HTBunga                       string      `json:"ht_bunga"`
			TglHapusTagih                 interface{} `json:"tglhapustagih"`
			TotalHT                       interface{} `json:"total_ht"`
			Tabungan                      []struct {
				ID              string `json:"id"`
				NamaNasabah     string `json:"namanasabah"`
				ProdukId        string `json:"produkid"`
				Tglbukarekening *Tgl   `json:"tglbukarekening"`
				Currency        string `json:"currency"`
				StatusDokumen   string `json:"status_dokumen"`
				Saldo           string `json:"saldo"`
				SaldoDebit      string `json:"saldodebit"`
			} `json:"tabungan"`
			JadwalAngsuran []struct {
				Tanggal      string `json:"tanggal"`
				Angsuran     string `json:"angsuran"`
				Bunga        string `json:"bunga"`
				Pokok        string `json:"pokok"`
				Denda        string `json:"denda"`
				BayarPokok   string `json:"bayar_pokok"`
				BayarDenda   string `json:"bayar_denda"`
				BayarBunga   string `json:"bayar_bunga"`
				SisaPinjaman string `json:"sisapinjaman"`
				StatusBayar  string `json:"statusbayar"`
				AngsuranKe   int64  `json:"angsuranke"`
			} `json:"jadwalangsuran"`
			HistoryBayar []struct {
				Tgl                 Tgl         `json:"tgl"`
				AngsuranKe          int64       `json:"angsuranke"`
				Tglbayar            string      `json:"tglbayar"`
				Currency            string      `json:"currency"`
				TglJt               string      `json:"tgljt"`
				TotalBayar          string      `json:"totalbayar"`
				BayarPokok          string      `json:"bayar_pokok"`
				BayarBunga          string      `json:"bayar_bunga"`
				BayarDenda          string      `json:"bayar_denda"`
				BayarDendaPelunasan string      `json:"bayar_dendapelunasan"`
				NominalDwp          string      `json:"nominaldwp"`
				NoJurnal            string      `json:"nojurnal"`
				Cabang              string      `json:"cabang"`
				Keterangan          interface{} `json:"keterangan"`
				Officer             string      `json:"officer"`
				Otor                string      `json:"otor"`
			} `json:"historybayar"`
			// could be false or map[string]interface{}
			TempatPenyimpanan       interface{} `json:"tempatpenyimpanan"`
			RekeningBungaBerjenjang []struct {
				ID           string  `json:"id"`
				Nourut       string  `json:"nourut"`
				Norekening   string  `json:"norekening"`
				Tenor        int64   `json:"tenor"`
				Bunga        float64 `json:"bunga"`
				RecTimestamp struct {
					Date string `json:"date"`
					Type string `json:"type"`
				} `json:"rec_timestamp"`
			} `json:"rekening_bungaberjenjang"`
			// could be false or map[string]interface{}
			Channeling   interface{} `json:"channeling"`
			AsuransiData string      `json:"asuransidata"`
		} `json:"result"`
	} `json:"data"`
	Status string `json:"status"`
}

type Tgl struct {
	Date         string `json:"date"`
	TimezoneType int64  `json:"timezone_type"`
	Timezone     string `json:"timezone"`
}
