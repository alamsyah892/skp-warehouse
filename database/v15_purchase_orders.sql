-- skp_warehouse.v15_purchase_orders source
create
or replace algorithm = UNDEFINED view `skp_warehouse`.`v15_purchase_orders` as
select
    `po`.`id` as `id`,
    `po`.`number` as `number`,
    `po`.`purchase_type_id` as `type`,
    ifnull (`po`.`notes_to_user`, '') as `description`,
    case `po`.`purchase_status_id`
        when 1 then 1
        when 2 then 2
        when 3 then 3
        when 4 then 2
        when 5 then 4
        else null
    end as `status`,
    `po`.`vendor_id` as `vendor_id`,
    `po`.`warehouse_2_id` as `company_id`,
    `po`.`warehouse_1_id` as `warehouse_id`,
    `po`.`warehouse_3_id` as `division_id`,
    `po`.`warehouse_4_id` as `project_id`,
    `pr`.`wh1_address_id` as `warehouse_address_id`,
    `po`.`user_id` as `user_id`,
    `beginning` as 'opening_letter',
    ifnull (`po_payment_terms`.`content`, '') as 'payment_terms',
    ifnull (`po_payment_conditions`.`content`, '') as `payment_conditions`,
    ifnull (`po_delivery_dates`.`content`, '') as `delivery_date`,
    ifnull (`po_delivery_notes`.`content`, '') as `delivery_notes`,
    ifnull (`po_terms`.`content`, '') as `po_terms`,
    ifnull (`po_terms_2`.`content`, '') as `po_terms_2`,
    '' as `notes`,
    ifnull (`po`.`info`, '') as `info`,
    `po`.`discount` as `discount`,
    'exclude' as `tax_type`,
    `po`.`tax` as `tax_percentage`,
    `po`.`tax_description` as `tax_description`,
    0 as `rounding`,
    `po`.`created_at` as `created_at`,
    `po`.`updated_at` as `updated_at`,
    `po`.`deleted_at` as `deleted_at`
from
    (
        (
            (
                (
                    (
                        (
                            (
                                (
                                    (
                                        (
                                            (
                                                (
                                                    (
                                                        `skp_warehouse`.`purchases` `po`
                                                        left join `skp_warehouse`.`purchase_content_letters` `po_delivery_dates` on (
                                                            `po_delivery_dates`.`purchase_id` = `po`.`id`
                                                            and `po_delivery_dates`.`title` = 'Tanggal Pengiriman' -- 1
                                                        )
                                                    )
                                                    left join `skp_warehouse`.`purchase_content_letters` `po_payment_terms` on (
                                                        `po_payment_terms`.`purchase_id` = `po`.`id`
                                                        and `po_payment_terms`.`title` = 'Syarat Pembayaran' -- 2
                                                    )
                                                )
                                                left join `skp_warehouse`.`purchase_content_letters` `po_payment_conditions` on (
                                                    `po_payment_conditions`.`purchase_id` = `po`.`id`
                                                    and `po_payment_conditions`.`title` = 'Cara Pembayaran' -- 3
                                                )
                                            )
                                            left join `skp_warehouse`.`purchase_content_letters` `po_terms` on (
                                                `po_terms`.`purchase_id` = `po`.`id`
                                                and `po_terms`.`title` = 'Keterangan' -- 4
                                            )
                                        )
                                        left join `skp_warehouse`.`purchase_content_letters` `po_terms_2` on (
                                            `po_terms_2`.`purchase_id` = `po`.`id`
                                            and `po_terms_2`.`title` = 'Terms' -- 5
                                        )
                                    )
                                    left join `skp_warehouse`.`purchase_content_letters` `po_terms_3` on (
                                        `po_terms_3`.`purchase_id` = `po`.`id`
                                        and `po_terms_3`.`title` = 'Tata Cara Pengajuan Penagihan' -- 6
                                    )
                                )
                                left join `skp_warehouse`.`purchase_content_letters` `po_terms_4` on (
                                    `po_terms_4`.`purchase_id` = `po`.`id`
                                    and `po_terms_4`.`title` = 'Ketentuan-ketentuan' -- 7
                                )
                            )
                            left join `skp_warehouse`.`purchase_content_letters` `po_terms_5` on (
                                `po_terms_5`.`purchase_id` = `po`.`id`
                                and `po_terms_5`.`title` = 'Tata Cara Pengajuan Opname' -- 8
                            )
                        )
                        left join `skp_warehouse`.`purchase_content_letters` `po_terms_5` on (
                            `po_terms_5`.`purchase_id` = `po`.`id`
                            and `po_terms_5`.`title` = 'Waktu Pengerjaan' -- 9
                        )
                    )
                    left join `skp_warehouse`.`purchase_content_letters` `po_delivery_notes` on (
                        `po_delivery_notes`.`purchase_id` = `po`.`id`
                        and `po_delivery_notes`.`title` = 'Waktu Kirim' -- 10
                    )
                )
                left join `skp_warehouse`.`purchase_content_letters` `po_site_locations` on (
                    `po_site_locations`.`purchase_id` = `po`.`id`
                    and `po_site_locations`.`title` = 'Lokasi' -- 11
                )
            )
            left join `skp_warehouse`.`purchase_content_letters` `po_others` on (
                `po_others`.`purchase_id` = `po`.`id`
                and `po_others`.`title` = 'Lain-lain' -- 12
            )
        )
        left join `skp_warehouse`.`requests` `pr` on (`po`.`request_id` = `pr`.`id`)
    );