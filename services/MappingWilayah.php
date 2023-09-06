<?php

namespace app\services;

use yii\helpers\Json;
use yii\db\Expression;
use yii\db\Query;
use Yii;

class MappingWilayah extends Service
{
    public $table = 'wilayah_2022';

    public function process($data)
    {
        echo "\n" . __FUNCTION__ . "\n";

        foreach ($data as $prov_id => $row) {
            if ($this->start['prov_id'] and $prov_id < $this->start['prov_id']) continue;

            $file = __DIR__."/../res/w/$row[nama]/index.json";
            $this->processProv($prov_id, $row, Json::decode(file_get_contents($file)));
        }
    }

    public function processProv($id, $row, $kab_list)
    {
        echo "\n" . __FUNCTION__ . "\n";
        $kode_wil = $this->syncProv($id, $row);

        foreach ($kab_list as $kab_id => $kab) {
            if ($this->start['kab_id'] and $kab_id < $this->start['kab_id']) continue;

            $file = __DIR__."/../res/w/$row[nama]/$kab[nama]/index.json";
            $this->processKab($kab_id, $kab, $row['nama'], $kode_wil, Json::decode(file_get_contents($file)));
        }
    }

    protected function processKab($id, $kab, $prefix, $kode_prov, $kec_list)
    {
        echo "\n" . __FUNCTION__ . "\n";

        $kode_wil = $this->syncKab($id, $kab, $prefix_id);
        $prefix .= "/$kab[nama]";

        foreach ($kec_list as $kec_id => $kec) {
            if ($this->start['kec_id'] and $kec_id < $this->start['kec_id']) continue;

            $file = __DIR__."/../res/w/$prefix/$kec[nama]/index.json";
            $this->processKec($kec_id, $kec, $prefix_id, Json::decode(file_get_contents($file)));
        }
    }

    protected function processKec($id, $kec, $prefix, $desa_list)
    {
        echo "\n" . __FUNCTION__ . "\n";
        $kode_wil = $this->syncKec($id, $kec);
        $prefix .= "/$kec[nama]";

        foreach ($desa_list as $desa_id => $desa) {
            if ($this->start['desa_id'] and $id < $this->start['desa_id']) continue;

            $this->syncDesa($desa_id, $desa);
        }
    }

    public function syncProv($id, $row)
    {
        echo "\n" . __FUNCTION__ . "\n";

        $is_prov = new Expression("length(kode) = 2");
        $query = (new Query)->select('kode')->from($this->table);

        if ($query->where($is_prov)->andWhere(["kpu_kode_id" => $id])->exists()) return;

        if (!$query->where($is_prov)->andWhere(["nama" => $row->nama])->exists()) return;

        Yii::$app->db->createCommand()->update($this->table, ['kpu_kode_id' => $id], $query)->execute();
    }

    public function syncKab($id, $row, $prov)
    {
        echo "\n" . __FUNCTION__ . "\n";

        $is_prov = new Expression("length(kode) = 5");
        $query = (new Query)->select('kode')->from($this->table);

        if ($query->where($is_prov)->andWhere(["kpu_kode_id" => $id])->exists()) return;

        if (!$query->where($is_prov)->andWhere(["nama" => $row->nama])->exists()) return;

        Yii::$app->db->createCommand()->update($this->table, ['kpu_kode_id' => $id], $query)->execute();
    }

    public function syncKec($id, $row, $kab)
    {
        echo "\n" . __FUNCTION__ . "\n";

        $is_prov = new Expression("length(kode) = 8");
        $query = (new Query)->select('kode')->from($this->table);

        if ($query->where($is_prov)->andWhere(["kpu_kode_id" => $id])->exists()) return;

        if (!$query->where($is_prov)->andWhere(["nama" => $row->nama])->exists()) return;

        Yii::$app->db->createCommand()->update($this->table, ['kpu_kode_id' => $id], $query)->execute();
    }

    public function syncDesa($id, $row, $kec)
    {
        echo "\n" . __FUNCTION__ . "\n";

        $is_prov = new Expression("length(kode) = 13");
        $query = (new Query)->select('kode')->from($this->table);

        if ($query->where($is_prov)->andWhere(["kpu_kode_id" => $id])->exists()) return;

        if (!$query->where($is_prov)->andWhere(["nama" => $row->nama])->exists()) return;

        Yii::$app->db->createCommand()->update($this->table, ['kpu_kode_id' => $id], $query)->execute();
    }
}
