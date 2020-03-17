<?php

class Config
{
    public static $connections = array(
        'source' => [
            'server'    => 'pslm.pbos.gov.pk',
            'user'      => 'pslm',
            'password'  => 'p$lm!920',
            'database'  => 'PSLM1920'
        ],

        'destination' => [
            'server'    => '10.1.1.6',
            'user'      => 'pslmUser',
            'password'  => 'PSLM@v!ews',
            'database'  => 'PSLM1920'
        ]
    );

    public static $database_structure = array(
        // "table_name" => [ primary_key columns list]
        //"AspNetRoles"  => ["Id"],
        //"AspNetUserClaims" => ["Id"],
        //"AspNetUserLogins" => ["UserId", "LoginProvider", "ProviderKey"],
        //"AspNetUserRoles" => ["UserId", "RoleId"],
        //"AspNetUsers" => ["UserId"],
        //"BackupFiles" => ["Id"],
        //"batch_check" => ["batch_id", "batch_no"],
        //"Batches" => ["Batch"],
        //"Dis_Description" => ["pcode", "dcode"],
        //"Dist" => ["dis_id"],
        //"DistCodes_Hies" => ["A2", "A3"],
        //"DistrictsMappings" => ["Id"],
        //"DummyTable" => ["UserId"],
        //"Field_offices" => ["ID"],
        //"FO_Users" => ["User_ID"],
        //"FO_Users_Supervisors" => ["User_ID"],
        //"FO_Users_v1" => ["User_ID"],
        //"get_FOID" => ["prcode"],
        //"get_FOID2" => ["prcode"],
        //"HH" => ["hh"],
        //"LoginActivityLog" => ["Id"],
        //"machine" => ["SNo"],
        //"PCD_FO" => ["Prcode", "hh"],
        //"PCD_FO_v1" => ["Prcode", "hh"],
        //"Sample" => ["sno1", "sno2"],
        //"Sample_2" => ["sr#No", "sr#no1"],
        //"Sample_Replacements" => ["sno1", "sno2"],
        //"Sample_RWP" => ["SNO1", "SNO2"],
        "SECTION_A" => ["Prcode"],
        "SECTION_B" => ["Prcode", "IDC"],
        "SECTION_B2" => ["Prcode", "IDC"],
        "SECTION_C1" => ["Prcode", "IDC"],
        "SECTION_C2" => ["Prcode", "IDC"],
        "SECTION_D" => ["Prcode", "IDC"],
        "SECTION_E" => ["Prcode", "IDC"],
        "SECTION_F1" => ["Prcode"],
        "SECTION_F2" => ["Prcode"],
        "SECTION_F3" => ["Prcode"],
        "SECTION_G" => ["Prcode", "IDC"],
        "SECTION_H" => ["Prcode", "IDC", "C01"],
        "SECTION_I" => ["Prcode", "CHID", "MID"],
        "SECTION_J" => ["Prcode", "IDC"],
        "SECTION_K" => ["Prcode"],
        "SECTION_L" => ["Prcode", "IDC"],
        //"TbBrandMst" => ["BrandId"],
        //"tblQuintile" => ["Quintile_ID"]
    );
}
