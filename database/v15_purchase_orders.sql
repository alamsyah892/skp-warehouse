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
    ifnull (`po`.`beginning`, '') as `po_opening_letter`, -- 0
    ifnull (`po_payment_terms`.`content`, '') as `po_payment_terms`, -- 1
    COALESCE(
        NULLIF(`po`.`delivery`, ''),
        NULLIF(`po_delivery_date`.`content`, ''),
        ''
    ) as `po_delivery_date`, -- 2
    COALESCE(
        NULLIF(`po`.`payment`, ''),
        NULLIF(`po_payment_method`.`content`, ''),
        ''
    ) as `po_payment_method`, -- 3
    COALESCE(
        NULLIF(`po`.`description`, ''),
        NULLIF(`po_description`.`content`, ''),
        ''
    ) as `po_description`, -- 4
    ifnull (`po_terms`.`content`, '') as `po_terms`, -- 5
    ifnull (`po_billing_method`.`content`, '') as `po_billing_method`, -- 6
    ifnull (`po_conditions`.`content`, '') as `po_conditions`, -- 7
    ifnull (`po_opname_method`.`content`, '') as `po_opname_method`, -- 8
    ifnull (`po_processing_time`.`content`, '') as `po_processing_time`, -- 9
    ifnull (`po_delivery_time`.`content`, '') as `po_delivery_time`, -- 10
    ifnull (`po_location`.`content`, '') as `po_location`, -- 11  
    ifnull (`po_miscellaneous`.`content`, '') as `po_miscellaneous`, -- 12
    ifnull (`po_warranty`.`content`, '') as `po_warranty`, -- 13
    COALESCE(
        NULLIF(`po_duration_info`.`content`, ''),
        NULLIF(`po_duration_info_2`.`content`, ''),
        NULLIF(`po_duration_info_3`.`content`, ''),
        NULLIF(`po_duration_info_4`.`content`, ''),
        NULLIF(`po_duration_info_5`.`content`, ''),
        NULLIF(`po_duration_info_6`.`content`, ''),
        NULLIF(`po_duration_info_7`.`content`, ''),
        NULLIF(`po_duration_info_8`.`content`, ''),
        ''
    ) AS `po_duration_info`, -- 14
    ifnull (`po`.`cc`, '') as `po_carbon_copy`,
    ifnull (`po`.`footnotes`, '') as `po_footnote`,
    ifnull (`po`.`notes_to_vendor`, '') as `notes`,
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
                                                                                            left join `skp_warehouse`.`purchase_content_letters` `po_payment_terms` on (
                                                                                                `po_payment_terms`.`purchase_id` = `po`.`id`
                                                                                                and `po_payment_terms`.`title` = 'Syarat Pembayaran'
                                                                                                -- 1
                                                                                            )
                                                                                        )
                                                                                        left join `skp_warehouse`.`purchase_content_letters` `po_delivery_date` on (
                                                                                            `po_delivery_date`.`purchase_id` = `po`.`id`
                                                                                            and `po_delivery_date`.`title` = 'Tanggal Pengiriman'
                                                                                            -- 2
                                                                                        )
                                                                                    )
                                                                                    left join `skp_warehouse`.`purchase_content_letters` `po_payment_method` on (
                                                                                        `po_payment_method`.`purchase_id` = `po`.`id`
                                                                                        and `po_payment_method`.`title` = 'Cara Pembayaran'
                                                                                        -- 3
                                                                                    )
                                                                                )
                                                                                left join `skp_warehouse`.`purchase_content_letters` `po_description` on (
                                                                                    `po_description`.`purchase_id` = `po`.`id`
                                                                                    and `po_description`.`title` = 'Keterangan'
                                                                                    -- 4
                                                                                )
                                                                            )
                                                                            left join `skp_warehouse`.`purchase_content_letters` `po_terms` on (
                                                                                `po_terms`.`purchase_id` = `po`.`id`
                                                                                and `po_terms`.`title` = 'Terms'
                                                                                -- 5
                                                                            )
                                                                        )
                                                                        left join `skp_warehouse`.`purchase_content_letters` `po_billing_method` on (
                                                                            `po_billing_method`.`purchase_id` = `po`.`id`
                                                                            and `po_billing_method`.`title` = 'Tata Cara Pengajuan Penagihan'
                                                                            -- 6
                                                                        )
                                                                    )
                                                                    left join `skp_warehouse`.`purchase_content_letters` `po_conditions` on (
                                                                        `po_conditions`.`purchase_id` = `po`.`id`
                                                                        and `po_conditions`.`title` = 'Ketentuan-ketentuan'
                                                                        -- 7
                                                                    )
                                                                )
                                                                left join `skp_warehouse`.`purchase_content_letters` `po_opname_method` on (
                                                                    `po_opname_method`.`purchase_id` = `po`.`id`
                                                                    and `po_opname_method`.`title` = 'Tata Cara Pengajuan Opname'
                                                                    -- 8
                                                                )
                                                            )
                                                            left join `skp_warehouse`.`purchase_content_letters` `po_processing_time` on (
                                                                `po_processing_time`.`purchase_id` = `po`.`id`
                                                                and `po_processing_time`.`title` = 'Waktu Pengerjaan'
                                                                -- 9
                                                            )
                                                        )
                                                        left join `skp_warehouse`.`purchase_content_letters` `po_delivery_time` on (
                                                            `po_delivery_time`.`purchase_id` = `po`.`id`
                                                            and `po_delivery_time`.`title` = 'Waktu Kirim'
                                                            -- 10
                                                        )
                                                    )
                                                    left join `skp_warehouse`.`purchase_content_letters` `po_location` on (
                                                        `po_location`.`purchase_id` = `po`.`id`
                                                        and `po_location`.`title` = 'Lokasi'
                                                        -- 11
                                                    )
                                                )
                                                left join `skp_warehouse`.`purchase_content_letters` `po_miscellaneous` on (
                                                    `po_miscellaneous`.`purchase_id` = `po`.`id`
                                                    and `po_miscellaneous`.`title` = 'Lain-lain'
                                                    -- 12
                                                )
                                            )
                                            left join `skp_warehouse`.`purchase_content_letters` `po_warranty` on (
                                                `po_warranty`.`purchase_id` = `po`.`id`
                                                and `po_warranty`.`title` = 'Garansi'
                                                -- 13
                                            )
                                        )
                                        left join `skp_warehouse`.`purchase_content_letters` `po_duration_info` on (
                                            `po_duration_info`.`purchase_id` = `po`.`id`
                                            and `po_duration_info`.`title` = 'Periode Sewa'
                                            -- 14
                                        )
                                    )
                                    left join `skp_warehouse`.`purchase_content_letters` `po_duration_info_2` on (
                                        `po_duration_info_2`.`purchase_id` = `po`.`id`
                                        and `po_duration_info_2`.`title` = 'Lama Pengadaan'
                                        -- 15
                                    )
                                )
                                left join `skp_warehouse`.`purchase_content_letters` `po_duration_info_3` on (
                                    `po_duration_info_3`.`purchase_id` = `po`.`id`
                                    and `po_duration_info_3`.`title` = 'Lama Pengadaan dan Pengerjaan'
                                    -- 16
                                )
                            )
                            left join `skp_warehouse`.`purchase_content_letters` `po_duration_info_4` on (
                                `po_duration_info_4`.`purchase_id` = `po`.`id`
                                and `po_duration_info_4`.`title` = 'Pengadaan Barang'
                                -- 17
                            )
                        )
                        left join `skp_warehouse`.`purchase_content_letters` `po_duration_info_5` on (
                            `po_duration_info_5`.`purchase_id` = `po`.`id`
                            and `po_duration_info_5`.`title` = 'Lama Pengerjaan'
                            -- 18
                        )
                    )
                    left join `skp_warehouse`.`purchase_content_letters` `po_duration_info_6` on (
                        `po_duration_info_6`.`purchase_id` = `po`.`id`
                        and `po_duration_info_6`.`title` = 'Lama Pemasangan'
                        -- 19
                    )
                )
                left join `skp_warehouse`.`purchase_content_letters` `po_duration_info_7` on (
                    `po_duration_info_7`.`purchase_id` = `po`.`id`
                    and `po_duration_info_7`.`title` = 'Lama Pengadaan &amp; Pengerjaan'
                    -- 20
                )
            )
            left join `skp_warehouse`.`purchase_content_letters` `po_duration_info_8` on (
                `po_duration_info_8`.`purchase_id` = `po`.`id`
                and `po_duration_info_8`.`title` = 'Lama Pengadaan &amp; Pekerjaan'
                -- 21
            )
        )
        left join `skp_warehouse`.`requests` `pr` on (`po`.`request_id` = `pr`.`id`)
    );