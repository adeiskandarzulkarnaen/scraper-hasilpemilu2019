<?php

namespace app\commands;

use app\services\FetchData;
use app\services\MappingWilayah;
use app\services\SaveToDatabase;
use yii\helpers\Json;
use Yii;
use yii\helpers\Console;

define('BASE', 'https://pemilu2019.kpu.go.id/static/json');

class ScrapController extends \yii\console\Controller
{
    /**
     * @var int start_index (prov_id/kab_id/kec_id/desa_id/tps_id)
     */
    public $start;
    /**
     * @var int end_index (prov_id/kab_id/kec_id/desa_id/tps_id)
     */
    public $end;
    /**
     * @var bool
     */
    public $overwrite;
    /**
     * @var string [ppwp|dprri|dprdprov|dprdkab|dpd]
     */
    public $type = 'ppwp';

    public function options($actionId)
    {
        return array_merge(parent::options($actionId), ['start','end','type','overwrite']);
    }

    public function parseRange()
    {
        $start = $this->start ? explode('/', $this->start) : [];
        $this->start = [];
        foreach (['prov_id','kab_id','kec_id','desa_id','tps_id'] as $i => $key) {
            $this->start[$key] = isset($start[$i]) ? $start[$i] : null;
        }

        $end = $this->end ? explode('/', $this->end) : [];
        $this->end = [];
        foreach (['prov_id','kab_id','kec_id','desa_id','tps_id'] as $i => $key) {
            $this->end[$key] = isset($end[$i]) ? $end[$i] : null;
        }
    }

    public function actionFetch()
    {
        $this->parseRange();

        $service = new FetchData(
            $this->start,
            $this->end,
            $this->type,
            $this->overwrite
        );

        $prov_list = $service->getWilayah();
        foreach ($prov_list as $id => $prov) {
            if ($this->start['prov_id'] and $id < $this->start['prov_id']) continue;
            if ($this->end['prov_id'] and $id > $this->end['prov_id']) break;
            $prov['id'] = $id;
            $service->processProv($prov);
        }
    }

    public function actionPartai()
    {
        $partais = json_decode(file_get_contents(__DIR__ . '/../res/partai.json'));

        foreach ($partais as $id => $partai) {
            Yii::$app->db->createCommand()->insert('partai', [
                'id' =>  $id,
                'nama' => $partai->nama,
                'warna' => $partai->warna,
            ])->execute();
        }
    }

    public function actionJson2sql()
    {
        $this->parseRange();

        $service = new SaveToDatabase();
        $service->start = $this->start;
        $service->end = $this->end;
        $service->type = $this->type;

        $file = __DIR__.'/../res/w/index.json';
        $data = Json::decode(file_get_contents($file));
        $service->process($data);
    }

    public function actionMappingWilayah()
    {
        $this->parseRange();

        $service = new MappingWilayah(
            $this->start,
            $this->end,
            $this->type,
            $this->overwrite,
        );

        $file = __DIR__.'/../res/w/index.json';
        $data = Json::decode(file_get_contents($file));
        $service->process($data);

    }

    // import data dari @app/rekap_suara-part0.txt
    // public function actionImport()
    // {
    //     // <!-- -- Adminer 4.8.2-dev MySQL 5.7.30-0ubuntu0.18.04.1 dump

    //     // SET NAMES utf8;
    //     // SET time_zone = '+00:00';
    //     // SET foreign_key_checks = 0;
    //     // SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

    //     // INSERT INTO `rekap_suara` (`pemilu_id`, `kelurahan_id`, `partai_id`, `jumlah_suara`) VALUES -->
    //     // (1,	'1/1207/1208/1209',	3,	6),
    //     // (1,	'1/1207/1208/1209',	4,	15),        


    //     $data = file(Yii::getAlias('@app/rekap_suara-part0.txt'));

    //     foreach ($data as $line) {
    //         if (!$line OR str_starts_with($line, 'INSERT INTO')) {
    //             continue;
    //         }

    //         $pemilu_id = $kel_id = $partai_id = $jml_suara = null;
    //         @list($pemilu_id, $kel_id, $partai_id, $jml_suara) = explode(',', $line);

    //         if ($pemilu_id && $kel_id && $partai_id && $jml_suara) {
    //             $pemilu_id = trim($pemilu_id, " \t\n(");
    //             $jumlah_suara = trim($jml_suara, " \t\n)");
    //             $kelurahan_id = trim($kel_id, "' \t\n");
    //             $partai_id = trim($partai_id);

    //             if ($jumlah_suara == 0) {
    //                 $jumlah_suara = null;
    //             }

    //             $data = compact('pemilu_id','partai_id','kelurahan_id','jumlah_suara');

    //             try {
    //                 Console::stdout('insert data '.print_r($data,1));
    //                 Yii::$app->db->createCommand()->insert('rekap_suara', $data)->execute();
    //             }
    //             catch(\Throwable $e) {
    //                 Console::error($e->getMessage());
    //             }
    //         }
    //     }
    // }

}
