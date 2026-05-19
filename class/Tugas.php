<?php

class Tugas
{
    protected $namaTugas;
    protected $statusTugas;
    protected $dueDate;
    protected $prioritas;
    protected $kategori;
    protected $userId;

    public function __construct(
        $namaTugas = '',
        $statusTugas = 'Belum Selesai',
        $dueDate = null,
        $prioritas = '',
        $kategori = '',
        $userId = 0
    ) {
        $this->namaTugas = $namaTugas;
        $this->statusTugas = $statusTugas;
        $this->dueDate = $dueDate;
        $this->prioritas = $prioritas;
        $this->kategori = $kategori;
        $this->userId = $userId;
    }

    public function setNamaTugas($namaTugas)
    {
        $this->namaTugas = $namaTugas;
    }

    public function setStatusTugas($statusTugas)
    {
        $this->statusTugas = $statusTugas;
    }

    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    public function setDueDate($dueDate)
    {
        $this->dueDate = $dueDate;
    }

    public function setPrioritas($prioritas)
    {
        $this->prioritas = $prioritas;
    }

    public function setKategori($kategori)
    {
        $this->kategori = $kategori;
    }

    public function getNamaTugas()
    {
        return $this->namaTugas;
    }

    public function getStatusTugas()
    {
        return $this->statusTugas;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function getDueDate()
    {
        return $this->dueDate;
    }

    public function getPrioritas()
    {
        return $this->prioritas;
    }

    public function getKategori()
    {
        return $this->kategori;
    }
}
