<?php
declare(strict_types = 1);

namespace CargoExpress\Delivery;

class DeliveryType
{
    /** @var int id типа доствки */
    protected $id;

    /** @var string название типа доствки */
    protected $name;

    /** @var int коэффициент. */
    protected $coefficient;

    /**
     * DeliveryType constructor.
     *
     * @param int $id
     * @param string $name
     * @param int $coefficient
     */
    public function __construct(int $id, string $name, int $coefficient = 1)
    {
        $this->id                = $id;
        $this->name              = $name;
        $this->coefficient       = $coefficient;
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
    public function getCoefficient(): int
    {
        return $this->coefficient;
    }
}
