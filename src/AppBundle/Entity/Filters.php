<?php

namespace AppBundle\Entity;

class Filters
{
    public function __construct($conn)
    {
        if ($conn) {
            $this->conn = $conn;
        }
    }

    /*
     * UTF -  Ocisti imena
     */
    public function cleanUTF($name)
    {
        $name = str_replace(array('š','č','đ','č','ć','ž','ñ','â'),array('s','c','d','c','c','z','n',''), $name);
        $name = str_replace(array('Š','Č','Đ','Č','Ć','Ž','Ñ','Â',' ','–'),array('S','C','D','C','C','Z','N','',' ','-'), $name);
        $name = str_replace(array('а','б','в','г','д','е','ё','ж','з','и','й','к','л','љ','м','н','њ','о','п','р','с','т','у','ф','х','ц','ч','џ','ш','щ','ъ','ы','ь','э','ю','я','А','Б','В','Г','Д','Е','Ё','Ж','З','И','Й','К','Л','Љ','М','Н','Њ','О','П','Р','С','Т','У','Ф','Х','Ц','Ч','Џ','Ш','Щ','Ъ','Ы','Ь','Э','Ю','Я'),
            array('a','b','v','g','d','e','e','z','z','i','j','k','l','lj','m','n','nj','o','p','r','s','t','u','f','h','c','c','dz','s','s','i','j','j','e','ju','ja','A','B','V','G','D','E','E','Z','Z','I','J','K','L','Lj','M','N','Nj','O','P','R','S','T','U','F','H','C','C','Dz','S','S','I','J','J','E','Ju','Ja'), $name);

        return $name;
    }

    /*
     * Izlistaj Brojeve sa neisporucenim porukama za kampanju
     */
    public function getUndelivered($messageId)
    {
        $sql = "SELECT origin FROM smsMessages
                WHERE messageId = '{$messageId}' AND status != 2 ORDER BY id DESC";

        $results = $this->conn->fetchAll($sql);
        return $results;
    }

    /*
     * Izlistaj odjavljene brojeve
     */
    public function getSuppressionList($state)
    {
        $sql = "SELECT * FROM suppressionList
                WHERE 1 ORDER BY id DESC";
        $results = $this->conn->fetchAll($sql);
        return $results;
    }

    /*
     * Izdvajanje mobilnog broja ako su dva upisana
     */
    public function pullCellNumber ($phoneNum, $state, $reportNum)
    {

        $alowedNo = array(
            'BA' => Array('38760','38761','38762','38763','38764','38765','38766','38767','060','061','062','063','064','065','066','067','60','61','62','63','64','65','66','67'),
            'RS' => Array('38160','38161','38162','38163','38164','38165','38166','38167','38168','38169','060','061','062','063','064','065','066','067','068','069','60','61','62','63','64','65','66','67','68','69'),
            'HR' => Array('38598','38599','38591','38595','385970','385976','385977','385979','38592','091','092','095','0970','0976','0977','0979','098','099','91','92','95','970','976','977','979','98','99'),
            'MK' => Array('38970','38971','38972','38973','38975','38976','38977','38978','070','071','072','073','075','076','077','078','70','71','72','73','75','76','77','78'),
            'BG' => Array('35987','35988','35989','359984','359985','359986','359987','359988','359989','087','088','089','0984','0985','0986','0987','0988','0989','87','88','89','984','985','986','987','988','989'),
            'SI' => Array('38630','38631','38640','38641','38651','38664','38668','38670','38671','030','031','040','041','051','064','068','070','071','30','31','40','41','51','64','68','70','71'),
            'IT' => Array('3932','3933','3934','3935','3936','3937','3938','3939','032','033','034','035','036','037','038','039','32','33','34','35','36','37','38','39'),
            'SK' => Array('42190','42191','42194','090','091','094','90','91','94'),
            'PL' => Array('4850','4851','4853','4857','4860','4866','4869','4872','4873','4878','4879','4888','050','051','053','057','060','066','069','072','073','078','079','088','50','51','53','57','60','66','69','72','73','78','79','88'),
            'GR' => Array('30685','30690','30691','30693','30694','30695','30696','30697','30698','30699','685','690','691','693','694','695','696','697','698','699'),
            'LV' => Array('3712','20','21','22','23','24','25','26','27','28','29'),
            'LT' => Array('37086','3706','86','60','61','62','63','61','64','65','66','67','68','69'),
            'AT' => Array('43650','43660','43664','43676','43677','43680','43681','43688','43699','0650','0660','0664','0676','0677','0680','0681','0688','0699','650','660','664','676','677','680','681','688','699'),
            'HU' => Array('3620','3630','3631','3670','020','030','031','070','20','30','31','70'),
            'CZ' => Array('42070','42072','42073','42077','42079','42091','420601','420602','420603','420604','420605','420606','420607','420608','070','072','073','077','079','091','0601','0602','0603','0604','0605','0606','0607','0608','70','72','73','77','79','91','601','602','603','604','605','606','607','608'),
            'RO' => Array('4071','4072','4073','4074','4075','4076','4077','4078','4079','071','072','073','074','075','076','077','078','079','71','72','73','74','75','76','77','78','79'),
            'DE' => Array('4915','4916','4917','015','016','017','15','16','17'),
            'EE' => Array('37250','37251','37252','37253','37254','37255','37256','37257','37258','37259','37281','37282','37283','050','051','052','053','054','055','056','057','058','059','081','082','083','50','51','52','53','54','55','56','57','58','59','81','82','83'),
            'FR' => Array('3360','3361','3362','3363','3364','3365','3366','3367','3368','3369','060','061','062','063','064','065','066','067','068','069','60','61','62','63','64','65','66','67','68','69'),
            'BE' => Array('3246','3247','3248','3249','046','047','048','049','46','47','48','49'),
            'ES' => Array('3460','3461','3462','3463','3464','3465','3466','3467','3468','3469','3471','3472','3473','3474','3475','3476','3477','3478','3479','060','061','062','063','064','065','066','067','068','069','071','072','073','074','075','076','077','078','079','60','61','62','63','64','65','66','67','68','69','71','72','73','74','75','76','77','78','79'),
            'AL' => Array('33566','33567','33568','33569','066','067','068','069','66','67','68','69'),
            'XK' => Array('37744','37745','044','045','44','45'),
            'VN' => Array('8412','8416','8418','8419','8486','8488','8489','8490','8491','8492','8493','8494','8496','8497','8498','8499','012','016','018','019','086','088','089','090','091','092','093','094','096','097','098','099','12','16','18','19','86','88','89','90','91','92','93','94','96','97','98','99'),
            'NG' => Array('23470','23480','23481','23490','070','080','081','090','70','80','81','90')
        );
        $splitPhone = "";
        $pulledNum = "";

        //Razdvajanje brojeva zavisno od toga kojim su znakom razdvojeni
        if (strpos($phoneNum,' ') !== false) {
            $splitPhone = explode(" ", $phoneNum);
        } else if (strpos($phoneNum,',') !== false) {
            $splitPhone = explode(",", $phoneNum);
        } else if (strpos($phoneNum,';') !== false) {
            $splitPhone = explode(";", $phoneNum);
        }

        foreach ($splitPhone as $brojevi) {

            foreach($alowedNo[$state] as $area){

                $duzina = strlen($area);
                $cutPhone = substr($brojevi, 0, $duzina);

                if ($area == $cutPhone) {
                    $pulledNum = $brojevi;

                    break;
                }
            }
        }
        $pulledNum = str_replace(',', "", $pulledNum);
        $pulledNum = str_replace(';', "", $pulledNum);
        $pulledNum = str_replace(' ', "", $pulledNum);
        $pulledNum = str_replace('.', "", $pulledNum);

        return $pulledNum;
    }
}
