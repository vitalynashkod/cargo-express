<?php
declare(strict_types = 1);

namespace CargoExpress\Delivery;

use CargoExpress\Client;
use CargoExpress\TransportModel;

class DeliveryContract
{
    /** @var Client */
    protected $client;

    /** @var TransportModel */
    protected $transportModel;

    /** @var float Стоимость */
    protected $price;

    /**
     * @var string
     */
    protected $startDate;

    /**
     * @var string
     */
    protected $endDate;

    /**
     * Статус контакта.  Поучает знаение 'signed' при подписании контракта клиентом.
     *
     * @var string
     */
    protected $status = 'in_progress';

    /**
     * DeliveryContract constructor.
     * @param Client $client
     * @param TransportModel $transportModel
     * @param string $startDate
     * @param float $price
     * @param string $endDate
     */
    public function __construct(Client $client, TransportModel $transportModel, string $startDate, float $price, string $endDate)
    {
        $this->client         = $client;
        $this->transportModel = $transportModel;
        $this->startDate      = $startDate;
        $this->price          = $price;
        $this->endDate        = $endDate;
    }

    /**
     * @return float
     */
    public function getPrice(): float
    {
        return $this->price;
    }

    /**
     * @param string $status
     */
    public function setStatus(string $status): void
    {
        $this->status = $status;
    }
}
