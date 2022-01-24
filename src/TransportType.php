<?php
declare(strict_types = 1);

namespace CargoExpress;

use CargoExpress\Delivery\DeliveryType;

class TransportType
{
    /** @var int id вида траспорта. */
    protected $id;

    /** @var string имя вида траспорта. */
    protected $name;

    /** @var int Ограничение по скорости. */
    protected $speed​​Limit;

    /** @var DeliveryType[] Типы доствки. */
    protected $deliveryTypes;

    /** @var TransportType  Родительский вид траспорта */
    protected $parentTransportType;

    /**
     * TransportType constructor.
     *
     * @param int $id
     * @param string $name
     * @param int $speed​​Limit
     * @param TransportType $parentTransportType
     */
    public function __construct(int $id, string $name, int $speed​​Limit = null, TransportType $parentTransportType = null)
    {
        $this->id                          = $id;
        $this->name                        = $name;
        $this->speed​​Limit                  = $speed​​Limit;
		$this->parentTransportType         = $parentTransportType;
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
     * @return int
     */
    public function getSpeed​​Limit(): ?int
    {
        if (!$this->speed​​Limit && $this->parentTransportType) {
            return $this->parentTransportType->getSpeed​​Limit();
        }

        return $this->speed​​Limit;
    }

    /**
     * @param DeliveryType $deliveryType
     */
    public function addDeliveryType($deliveryType): void
    {
        $this->deliveryTypes[] = $deliveryType;
    }

    /**
     * @return DeliveryType[]
     */
    public function getDeliveryTypes(): array
    {
        if (count($this->deliveryTypes) == 0 && $this->parentTransportType) {
            return $this->parentTransportType->getSpeed​​Limit();
        }

        return $this->deliveryTypes;
    }

    /**
     * @return TransportType
     */
    public function getParentTransportType(): ?TransportType
    {
        return $this->parentTransportType;
    }
}