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
        - jika user memiliki warehouses, tampilkan opsi warehouse berdasarkan warehouses user tersebut
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
    - ketentuan perubahan data PR dan PR status sebagai berikut:
        a. saat PR status DRAFT
            - PR status bisa diubah menjadi CANCELED atau REQUESTED (oleh PROJECT_OWNER, ADMINISTRATOR, LOGISTIC, LOGISTIC_MANAGER, PURCHASING, PURCHASING_MANAGER)
            - data PR bisa di-edit, nomor urut revisi tidak akan ditambahkan pada nomor PR setelah di-edit

        b. saat PR status CANCELED
            - PR status bisa diubah menjadi DRAFT atau REQUESTED atau APPROVED atau ORDERED (oleh PROJECT_OWNER, ADMINISTRATOR)
            - data PR tidak bisa di-edit

        c. saat PR status REQUESTED
            - PR status bisa diubah menjadi CANCELED atau APPROVED (oleh PROJECT_OWNER, ADMINISTRATOR, QUANTITY_SURVEYOR, AUDIT, AUDIT_MANAGER, PURCHASING, PURCHASING_MANAGER)
            - data PR bisa di-edit, nomor urut revisi akan ditambahkan pada nomor PR setelah di-edit

        d. saat PR status APPROVED
            - PR status bisa diubah menjadi CANCELED (oleh PROJECT_OWNER, ADMINISTRATOR, QUANTITY_SURVEYOR, AUDIT, AUDIT_MANAGER, PURCHASING, PURCHASING_MANAGER)
            - data PR bisa di-edit, nomor urut revisi akan ditambahkan pada nomor PR setelah di-edit

        e. saat PR status ORDERED
            - PR status bisa diubah menjadi CANCELED (oleh PROJECT_OWNER, ADMINISTRATOR, PURCHASING, PURCHASING_MANAGER) 
            atau FINISHED (oleh PROJECT_OWNER, ADMINISTRATOR, LOGISTIC, LOGISTIC_MANAGER, PURCHASING, PURCHASING_MANAGER)
            - data PR bisa di-edit, nomor urut revisi akan ditambahkan pada nomor PR setelah di-edit

        f. saat PR status FINISHED
            - data PR tidak bisa di-edit
        
    - button/action CANCELED PR disembunyikan, jika PR sudah punya PO dan semua PO tersebut status-nya bukan CANCELED

    - button/action FINISHED PR disembunyikan, jika PR sudah punya PO dan semua PO tersebut status-nya bukan FINISHED, dan masih ada PR Item yang Qty PO-nya masih sisa (PR Item->getRemainingQty() > 0)

    - saat user klik button/action untuk perubahan PR status, buat Log/Info perubahan status dengan setStatusLog()

    - tampilkan Qty PO (ordered_qty) di PR item jika item tersebut sudah punya PO Item dan semua PO tersebut status-nya bukan CANCELED

    - tampilkan Tab PO (list PO) jika PR tersebut sudah punya PO

4. PurchaseRequestForm (edit)
    a. form edit
        - disable select warehouse, company, division, dan project karena berhubungan dengan nomor PR
        - enable dan required textInput info jika user mengubah warehouseAddress, description, memo, dan boq
        - enable dan required textInput info jika user mengubah PR item seperti:
            - mengubah item, qty, dan description PR item
            - menambahkan PR item
            - menghapus PR item
        - tampilkan ordered_qty di PR item jika PR item tersebut sudah dibuat PO dan semua PO tersebut status-nya bukan CANCELED
        - jika PR item sudah dibuat PO dan PO tersebut status-nya bukan CANCELED, minimum qty nya adalah ordered_qty (jumlah qty PO)
        - jika PR item sudah dibuat PO dan PO tersebut status-nya bukan CANCELED, PR item tersebut tidak dapat dihapus
        - jika PR item sudah dibuat PO dan PO tersebut status-nya bukan CANCELED, item dari PR item tersebut tidak dapat diubah (disabled)
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
    - menampilkan semua data PO
    - jika user memiliki warehouses, data PO ditampilkan hanya yang warehouse nya berdasarkan warehouses user tersebut
    - terdapat tab setiap PO status untuk memfilter data berdasarkan PO status (dengan count pada status DRAFT, dan ORDERED)
    - table bisa di filter berdasarkan vendors, warehouses, companies, divisions, projects, dan deleted data
    - tambahkan kolom jumlah item dan jumlah GoodsReceives (GR)

2. PurchaseOrderForm (create)
    a. form create
        - user harus memilih PO type dulu
        - user harus memilih vendor dulu
        - tampilkan section informasi vendor berdasarkan vendor yang telah dipilih
        - user harus memilih warehouse dulu
        - jika user memiliki warehouses, tampilkan opsi warehouse berdasarkan warehouses user tersebut
        - dari warehouse yang telah dipilih, tampilkan opsi company berdasarkan warehouse->companies
        - dari warehouse yang telah dipilih, tampilkan opsi warehouseAddress berdasarkan warehouse->addresses
        - user harus memilih company
        - dari company yang telah dipilih, tampilkan opsi division berdasarkan company->divisions
        - dari company dan warehouse yang telah dipilih, tampilkan opsi project berdasarkan company->projects atau warehouse->projects
        - user harus memilih division
        - user harus memilih project
        - dari warehouse, company, division, dan project yang telah dipilih, filter list option PurchaseRequests berdasarkan PR warehouse, company, division, dan project.
        - jika warehouse, company, division, dan project belum dipilih, tampilkan semua data PR di list option PurchaseRequests
        - list option PurchaseRequests hanya tampilkan yang status nya APPROVED atau ORDERED
        - jika user memiliki warehouses, tampilkan data PR di list option PurchaseRequests yang warehouse nya ada di warehouses user tersebut
        - jika user memilih PurchaseRequest dulu sebelum memilih warehouse, company, division, dan project, set select warehouse, company, division, dan project berdasarkan warehouse, company, division, dan project dari PurchaseRequest tersebut
        - tampilkan section informasi PurchaseRequest berdasarkan PurchaseRequests yang telah dipilih
        - untuk warehouseAddress, dan tax_percentage boleh tidak dipilih. description, delivery_date, shipping_method, delivery_notes, terms, tax_description, discount, rounding, dan notes boleh tidak diisi
        
        - PO item harus diinput minimal 1 item
        - jika select PurchaseRequests sudah dipilih, enable-kan select purchase_request_item_id
        - list option purchase_request_item_id diambil dari PurchaseRequestItems dari PurchaseRequests yang telah dipilih
        - pada list option purchase_request_item_id, buat item-nya di-grouping berdasarkan PR number
        - jika user memilih purchase_request_item_id, set PO item item_id, qty, dan description, berdasarkan purchase_request_item_id tersebut. untuk qty nya berdasarkan remaining_qty (dari perhitungan selisih antara PR item dengan PO item) dari purchase_request_item_id tersebut. tampilkan juga hint request_qty, ordered_qty, dan remaining_qty. disable kan juga item_id nya.
        - jika user menghapus purchase_request_item_id yang telah dipilih, set PO item item_id, qty, price, dan description menjadi kosong
        - user dapat memilih PO item item_id yang opsi nya dari model Item (dengan mencari berdasarkan item->code atau item->name) tanpa harus memilih purchase_request_item_id
        - PO item item_id, qty, dan price harus diisi
        - tampilkan subtotal berdasarkan qty dikali price
        - tampilkan info/textentry PO item received_qty
        - qty maksimal adalah remaining_qty dari purchase_request_item_id tersebut
        - qty minimal adalah 0.01, jika sudah ada received_qty maka qty minimal adalah received_qty
        - untuk description PO item boleh tidak diisi
        - dalam PO item boleh terdapat item_id yang sama, tapi tidak boleh ada purchase_request_item_id yang sama
        - tampilkan rincian total

    b. setelah submit / creating
        - PO user_id diisi dengan auth user id
        - PO number diisi berdasarkan PurchaseOrderType->initial/y/m/project->po_code/division->code/(nomor urut tiga digit dengan angka 0 didepan dulu misal: 001)
        - PO status diisi dengan default (DRAFT)
        - periksa/validasi ulang terhadap perubahan PR sebelum data disimpan, jika terdapat perubahan PR status (menjadi CANCELED) atau PR item tidak sesuai dengan PO item yang akan disimpan.

    c. setelah data disimpan / created
        - setStatusLog() dengan value note dari PO number

3. PurchaseOrderInfolist
    - tampilkan button/action untuk perubahan PO status dengan ketentuan sebagai berikut:
        a. saat PO status DRAFT
            - tampilkan button/action CANCELED dan ORDERED (untuk user PROJECT_OWNER, ADMINISTRATOR, PURCHASING, PURCHASING_MANAGER)
            - ketika user klik ORDERED, tambahkan juga setStatusLog() untuk PR dari PO tersebut dan sertakan notes nya dengan PO number tersebut
            
        b. saat PO status CANCELED
            - data PO tidak bisa di-edit

        c. saat PO status ORDERED
            - tampilkan button/action CANCELED dan FINISHED (untuk user PROJECT_OWNER, ADMINISTRATOR, PURCHASING, PURCHASING_MANAGER)
    
    - sembunyikan button/action CANCELED jika PO sudah dibuat GoodsReceives, dan semua GoodsReceives tersebut status-nya bukan CANCELED
    - sembunyikan button/action FINISHED jika qty PO item ada yang belum dibuat GoodsReceives (baru diterima sebagian)
    - saat user klik button/action untuk perubahan PO status, buat infonya dengan setStatusLog()

    - tampilkan received_qty di PO item jika PO tersebut sudah dibuat GoodsReceives dan semua GoodsReceives tersebut status-nya bukan CANCELED


4. PurchaseOrderForm (edit)
    a. form edit
        - disable select warehouse, company, division, dan project karena berhubungan dengan nomor PO
        - enable dan required textInput info jika user mengubah warehouseAddress, description, delivery_date, shipping_method, delivery_notes, terms, tax_type, tax_percentage, tax_description, discount, rounding
        - enable dan required textInput info jika user mengubah PO item seperti:
            - mengubah item_id, qty, price, dan description PO item
            - menambahkan PO item
            - menghapus PO item
        - tampilkan received_qty di PO item jika PO item tersebut sudah dibuat GR dan semua GR tersebut status-nyabukan CANCELED
        - jika PO item sudah dibuat GR dan GR tersebut status-nya bukan CANCELED, minimum qty nya adalah received_qty (jumlah qty GR)
        - jika PO item sudah dibuat GR dan GR tersebut status-nya bukan CANCELED, PO item tersebut tidak dapat dihapus
        - jika PO item sudah dibuat GR dan GR tersebut status-nya bukan CANCELED, item dari PO tersebut tidak dapat diubah (disabled)
        - textInput info value nya kosongkan dulu
        - ketika textInput info disabled, value nya harus kosong

        - jangan tampilkan terms, section Ringkasan Total, textInput price, dan subtotal PO item jika user group nya dalam (LOGISTIC, LOGISTIC_MANAGER)

    b. setelah submit / saving
        - jika PO status masih DRAFT, tidak perlu ada perubahan pada PO number
        - jika data berubah sehingga memicu textInput info harus diisi, tambahkan nomor urut revisi setelah PO number
        - modifikasi value info dengan menambahkan value info sebelumnya sitambah value info yang baru. dan value info yang baru ditambahkan dulu dengan nomor revisi di awal/sebelum value info

    c. setelah data tersimpan / after save
        - refresh data PO number dan textEntry info, dan pastikan textInput info kosong lagi


- jika User memiliki Warehouses, list di PR table berdasarkan User Warehouses, opsi warehouse_id di PR filter berdasarkan User Warehouses
- jika User memiliki Warehouses, opsi warehouse_id di PR form berdasarkan User Warehouses
- saat PR dibuat, default status-nya adalah DRAFT
- saat PR status = DRAFT
    - PR bisa di CANCELED atau REQUESTED (oleh LOGISTIC, LOGISTIC_MANAGER, atau oleh user yang membuat PR)
    - PR bisa di-edit tanpa mengubah nomor Revisi

PR dapat dibatalkan 
