<?php
declare(strict_types = 1);

namespace CargoExpress\Delivery;

interface DeliveryTypeRepository
{
    /**
     * Возвращает тип доставки по id
     *
     * @param int $id
     * @return DeliveryType
     */
    public function getById(int $id): DeliveryType;
}