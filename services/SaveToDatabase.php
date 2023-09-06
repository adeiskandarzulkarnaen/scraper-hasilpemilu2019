<?php

namespace app\services;

use Yii;
use yii\helpers\Json;

class SaveToDatabase
{
    public $start;
    public $type;
    public $table = 'rekap_suara_tps';
    private $types = ['prov','kab','kec','desa'];

    public function process($data, $prefix=null, $prefix_id=null)
    {
        $lvl = substr_count($prefix_id,'/');
        if ($lvl === 4) {
            return $this->processDesa($data, $prefix, $prefix_id);
        }
        print_r(([
            '$prefix' => $prefix,
            '$prefix_id' => $prefix_id,
            '$lvl' => $lvl,
            '$type' => $this->types[$lvl],
        ]));
        foreach ($data as $id => $wil) {
            $start = $this->start[$this->types[$lvl].'_id'];
            echo PHP_EOL.$this->types[$lvl].PHP_EOL;
            echo 'ID: '.$id.PHP_EOL;
            echo 'start: '.$start.PHP_EOL;
            if ($start and $id < $start) {
                echo 'Skipping '.$this->types[$lvl].'_id: '.$id.PHP_EOL;
                continue;
            }
            $file = __DIR__."/../res/w$prefix/$wil[nama]/index.json";
            echo 'Get from cache: '.$file.PHP_EOL;
            $data = Json::decode(file_get_contents($file));
            $this->process($data, "$prefix/$wil[nama]", "$prefix_id$id/");
        }
    }

    public function processDesa($tps_list, $prefix, $prefix_id)
    {
        $types = ['ppwp'=>1, 'dprri'=>2, 'dprdprov'=>3, 'dprdkab'=>4];

        foreach ($tps_list as $id => $tps) {
            $start = $this->start['tps_id'];
            if ($start and $id < $start) {
                echo 'Skipping tps_id: '.$id.PHP_EOL;
                continue;
            }
            $file = __DIR__."/../res/w$prefix/{$this->type}/$tps[nama].json";
            $data = Json::decode(file_get_contents($file));
            $this->processTps($data, $prefix, $prefix_id.$id, $types[$this->type]);
        }
    }

    public function processTps($data_tps, $prefix, $prefix_id, $pemilu_id)
    {
        foreach (($data_tps['chart'] ??[]) as $partai_id => $jml_suara) {
            $data = [$pemilu_id, $prefix_id, $partai_id, $jml_suara];
            print "\npemilu_id=$pemilu_id tps_id=$prefix partai=$partai_id\n";
            echo 'data to save: ';print_r($data);
            
            Yii::$app->db->createCommand($sql = sprintf("
                INSERT INTO {$this->table} (pemilu_id, tps_id, partai_id, jumlah_suara) VALUES (%u,'%s',%u,%u)
                ON DUPLICATE KEY UPDATE 
                    pemilu_id=VALUES(pemilu_id),
                    tps_id=VALUES(tps_id),
                    partai_id=VALUES(partai_id),
                    jumlah_suara=VALUES(jumlah_suara)
            ", ...$data))->execute();
            echo 'Execute: '. $sql.PHP_EOL;
        }


        foreach ([
            101 => "pemilih_j",
            102 => "pengguna_j",
            103 => "suara_sah",
            104 => "suara_tidak_sah",
            105 => "suara_total",
        ] as $partai => $key) {
            if (empty($data_tps[$key])) continue;
            $data = [$pemilu_id, $prefix_id, $partai, $data_tps[$key]];
            print "\npemilu_id=$pemilu_id tps_id=$prefix partai=$partai\n";
            echo 'data to save: ';print_r($data);
            
            Yii::$app->db->createCommand($sql = sprintf("
                INSERT INTO {$this->table} (pemilu_id, tps_id, partai_id, jumlah_suara) VALUES (%u,'%s',%u,%u)
                ON DUPLICATE KEY UPDATE 
                    pemilu_id=VALUES(pemilu_id),
                    tps_id=VALUES(tps_id),
                    partai_id=VALUES(partai_id),
                    jumlah_suara=VALUES(jumlah_suara)
            ", ...$data))->execute();
            echo 'Execute: '. $sql.PHP_EOL;
        }
    }
}
