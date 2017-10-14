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
     * prebrojavanje kolicine smsova na brojeve by state, by product
     */
    public function countSmsPerNumber($sender, $product)
    {
        $sql = "SELECT MAX(dateSent) AS dateSent, origin, COUNT(*) as ukupno, CampManagement.price AS smsPrice FROM `smsMessages`
                LEFT JOIN CampManagement ON smsMessages.messageId = CampManagement.CampaignName
                WHERE CampManagement.product = {$product}
                AND smsMessages.from = '{$sender}'
                AND Date(smsMessages.dateSent) >= NOW() - INTERVAL 3 MONTH
                GROUP BY origin";

        $results = $this->conn->fetchAll($sql);
        return $results;
    }
}