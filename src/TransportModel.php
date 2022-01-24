<?php
declare(strict_types = 1);

namespace CargoExpress;

class TransportModel
{
    /** @var int id модели транспорта */
    protected $id;

    /** @var string название модели транспорта */
    protected $name;

    /** @var float Стоимость модели транспорта за килеметр движения */
    protected $pricePerKilometer;

    /** @var TransportType Вид траспорта */
    protected $transportType;

    /** @var int максимальная скорость траспорта */
    protected $fullSpeed;

    /**
     * TransportModel constructor.
     *
     * @param int $id
     * @param string $name
     * @param float $pricePerKilometer
     * @param TransportType $parentTransportType
     * @param int $fullSpeed
     */
    public function __construct(int $id, string $name, float $pricePerKilometer, TransportType $transportType, int $fullSpeed)
    {
        $this->id                          = $id;
        $this->name                        = $name;
        $this->pricePerKilometer           = $pricePerKilometer;
        $this->transportType               = $transportType;
        $this->fullSpeed                   = $fullSpeed;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return float
     */
    public function getPricePerKilometer(): float
    {
        return $this->pricePerKilometer;
    }

    /**
     * @return TransportType
     */
    public function getTransportType(): TransportType
    {
        return $this->transportType;
    }

    /**
     * @return int
     */
    public function getFullSpeed(): int
    {
        return $this->fullSpeed;
    }
}