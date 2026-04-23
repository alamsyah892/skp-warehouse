Modul PurchaseRequests (PR / Pengajuan Pembelian)
1. PurchaseRequestsTable
- menampilkan semua data PR
- jika user memiliki warehouses, data PR ditampilkan hanya yang warehouse nya berdasarkan warehouses user tersebut
- terdapat tab setiap PR status untuk memfilter data berdasarkan PR status (dengan count pada status DRAFT, REQUESTED, APPROVED, dan ORDERED)
- table bisa di filter berdasarkan warehouses, companies, divisions, projects, dan deleted data
- tambahkan kolom jumlah item dan jumlah PO

2. PurchaseRequestForm (create)
a. form create
    - user harus memilih warehouse dulu
    - dari warehouse yang telah dipilih, tampilkan opsi company berdasarkan warehouse->companies
    - dari warehouse yang telah dipilih, tampilkan opsi warehouseAddress berdasarkan warehouse->addresses
    - user harus memilih company
    - dari company yang telah dipilih, tampilkan opsi division berdasarkan company->divisions
    - dari company dan warehouse yang telah dipilih, tampilkan opsi project berdasarkan company->projects atau warehouse->projects
    - user harus memilih division
    - user harus memilih project
    - untuk warehouseAddress boleh tidak dipilih, description, memo, boq, dan notes boleh tidak diisi
    - PR item harus diinput minimal 1 item,
    - user harus memilih item PR item yang opsi nya dari model Item (dengan mencari berdasarkan item->code atau item->name)
    - user harus mengisi qty PR item (dengan minimal 0.01)
    - untuk description PR item boleh tidak diisi
    - dalam PR item boleh terdapat item yang sama

b. setelah submit / creating
    - PR user_id diisi dengan auth user id
    - PR type diisi dengan default (1)
    - PR number diisi berdasarkan PurchaseRequestType->initial/y/m/project->po_code/division->code/(nomor urut tiga digit dengan angka 0 didepan dulu misal: 001)
    - PR status diisi dengan default (DRAFT)

c. setelah data disimpan / created
    - setStatusLog() dengan value note dari PR number

3. PurchaseRequestInfolist
- tampilkan button/action untuk perubahan PR status dengan ketentuan sebagai berikut:
a. saat PR status DRAFT
    - tampilkan button/action CANCELED dan REQUESTED (untuk user PROJECT_OWNER, ADMINISTRATOR, LOGISTIC, LOGISTIC_MANAGER, PURCHASING, PURCHASING_MANAGER)
b. saat PR status CANCELED
    - data PR tidak bisa di-edit
c. saat PR status REQUESTED
    - tampilkan button/action CANCELED dan APPROVED (untuk user PROJECT_OWNER, ADMINISTRATOR, QUANTITY_SURVEYOR, AUDIT, AUDIT_MANAGER, PURCHASING, PURCHASING_MANAGER)
d. saat PR status APPROVED
    - tampilkan button/action CANCELED (untuk user PROJECT_OWNER, ADMINISTRATOR, QUANTITY_SURVEYOR, AUDIT, AUDIT_MANAGER, PURCHASING, PURCHASING_MANAGER)
e. saat PR status ORDERED
    - tampilkan button/action FINISHED (untuk user PROJECT_OWNER, ADMINISTRATOR, LOGISTIC, LOGISTIC_MANAGER, PURCHASING, PURCHASING_MANAGER) dan CANCELED (untuk user PROJECT_OWNER, ADMINISTRATOR, PURCHASING, PURCHASING_MANAGER)
    
- sembunyikan button/action CANCELED jika PR sudah dibuat PO dan semua PO tersebut statusnya dalam (DRAFT, ORDERED, FINISHED)
- sembunyikan button/action FINISHED jika qty PR item ada yang belum dibuat PO (baru dipesan sebagian) atau semua PO status nya bukan FINISHED
- saat user klik button/action untuk perubahan PR status, buat infonya dengan setStatusLog()

- tampilkan ordered_qty di PR item jika PR tersebut sudah dibuat PO dan semua PO tersebut statusnya bukan CANCELED

4. PurchaseRequestForm (edit)
a. form edit
- disable select warehouse, company, division, dan project karena berhubungan dengan nomor PR
- enable dan required textInput info jika user mengubah warehouseAddress, description, memo, dan boq
- enable dan required textInput info jika user mengubah PR item seperti:
    - mengubah item, qty, dan description PR item
    - menambahkan PR item
    - menghapus PR item
- tampilkan ordered_qty di PR item jika PR item tersebut sudah dibuat PO dan semua PO tersebut statusnya bukan CANCELED
- jika PR item sudah dibuat PO dan PO tersebut statusnya bukan CANCELED, minimum qty nya adalah ordered_qty (jumlah qty PO)
- jika PR item sudah dibuat PO dan PO tersebut statusnya bukan CANCELED, PR item tersebut tidak dapat dihapus
- jika PR item sudah dibuat PO dan PO tersebut statusnya bukan CANCELED, item dari PR item tersebut tidak dapat diubah (disabled)
- textInput info value nya kosongkan dulu
- ketika textInput info disabled, value nya harus kosong

b. setelah submit / saving
- jika PR status masih DRAFT, tidak perlu ada perubahan pada PR number
- jika data berubah sehingga memicu textInput info harus diisi, tambahkan nomor urut revisi setelah PR number
- modifikasi value info dengan menambahkan value info sebelumnya ditambah value info yang baru. dan value info yang baru ditambahkan dulu dengan nomor revisi di awal/sebelum value info

c. setelah data tersimpan / after save
- refresh data PR number dan textEntry info, dan pastikan textInput info kosong lagi


Modul PO
1. PurchaseOrderTable
2. PurchaseOrderForm (create)
- pada list options Sumber Item Pengajuan, buat item di-grouping berdasarkan PR number
3. PurchaseOrderInfolist
- ketika user klik pesan, tambahkan juga setStatusLog() untuk PR dari PO tersebut dan sertakan notes nya dengan PO number tersebut
4. PurchaseOrderForm (edit)


