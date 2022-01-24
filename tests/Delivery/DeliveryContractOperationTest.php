<?php
declare(strict_types = 1);

namespace CargoExpress\Delivery;

use PHPUnit\Framework\TestCase;
use CargoExpress\Client;
use CargoExpress\ClientsRepository;
use CargoExpress\TransportModel;
use CargoExpress\TransportModelsRepository;
use CargoExpress\TransportType;

class DeliveryContractOperationTest extends TestCase
{
    /**
     * Stub репозитория клиентов
     *
     * @param Client[] ...$clients
     * @return ClientsRepository
     */
    private function makeFakeClientRepository(...$clients): ClientsRepository
    {
        $clientsRepository = $this->prophesize(ClientsRepository::class);
        foreach ($clients as $client) {
            $clientsRepository->getById($client->getId())->willReturn($client);
        }

        return $clientsRepository->reveal();
    }

    /**
     * Stub репозитория моделей транспорта
     *
     * @param TransportModel[] ...$transportModels
     * @return TransportModelsRepository
     */
    private function makeFakeTransportModelRepository(...$transportModels): TransportModelsRepository
    {
        $transportModelsRepository = $this->prophesize(TransportModelsRepository::class);
        foreach ($transportModels as $transportModel) {
            $transportModelsRepository->getById($transportModel->getId())->willReturn($transportModel);
        }

        return $transportModelsRepository->reveal();
    }

    /**
     * Если транспорт занят, то нельзя его арендовать
     */
    public function test_periodIsBusy_failedWithOverlapInfo()
    {
        // -- Arrange
        {
            // Клиенты
            $client1    = new Client(1, 'Джонни');
            $client2    = new Client(2, 'Роберт');
            $clientRepo = $this->makeFakeClientRepository($client1, $client2);

            //Типы доствки
            $expressDelivery = new DeliveryType(1, 'Экспресс доставка', 2);
            $regularDelivery = new DeliveryType(2, 'Обычная доставка');

            // Stub репозитория типов доставки
            $deliveryTypeRepo = $this->prophesize(DeliveryTypeRepository::class);
            $deliveryTypeRepo
                ->getById($regularDelivery->getId())
                ->willReturn($regularDelivery);

            // Виды траспорта
            $groundTransportType = new TransportType(1, 'Наземный транспорт', 50);
            $groundTransportType->addDeliveryType($expressDelivery);
            $groundTransportType->addDeliveryType($regularDelivery);

            $airTransportType = new TransportType(2, 'Воздушный транспорт');
            $airTransportType->addDeliveryType($expressDelivery);

            $waterTransport = new TransportType(3, 'Водный транспорт');
            $waterTransport->addDeliveryType($expressDelivery);
            $waterTransport->addDeliveryType($regularDelivery);


            $railwayTransport = new TransportType(4, 'Железнодорожный транспорт', null, $groundTransportType);
            $railwayTransport->addDeliveryType($regularDelivery);

            $shippingTransport = new TransportType(5, 'Судоперевозки', null, $waterTransport);
            $shippingTransport->addDeliveryType($regularDelivery);

            $international = new TransportType(3, 'Международные', null, $airTransportType);

            // Модель транспорта
            $transportModel1 = new TransportModel(1, 'Турбо Пушка', 20, $railwayTransport, 100);

            $transportModelsRepo = $this->makeFakeTransportModelRepository($transportModel1);

            // Контракт доставки. 1й клиент арендовал транпорт 1
            $deliveryContract = new DeliveryContract($client1, $transportModel1, '2020-01-01 00:00', 50, '2020-01-01 20:00');

            // Stub репозитория договоров
            $contractsRepo = $this->prophesize(DeliveryContractsRepository::class);
            $contractsRepo
                ->getForTransportModel($transportModel1->getId(), '2020-01-01 10:00')
                ->willReturn([ $deliveryContract ]);

            $contractsRepo
                ->getCountByClientId($client2->getId())
                ->willReturn(0);

            // Запрос на новую доставку. 2й клиент выбрал время когда транспорт занят.
            $deliveryRequest = new DeliveryRequest($client2->getId(), $transportModel1->getId(), '2020-01-01 10:00', 'Нью-Йорк', $regularDelivery->getId());

            // Операция заключения договора на доставку
            $deliveryContractOperation = new DeliveryContractOperation($contractsRepo->reveal(), $clientRepo, $transportModelsRepo, $deliveryTypeRepo->reveal());
        }

        // -- Act
        $response = $deliveryContractOperation->execute($deliveryRequest);

        // -- Assert
        $this->assertCount(1, $response->getErrors());

        $message = 'Извините Турбо Пушка занята 2020-01-01 10:00';
        $this->assertStringContainsString($message, $response->getErrors()[0]);
    }

    /**
     * Если транспорт свободен, то его легко можно арендовать
     */
    public function test_successfullyOperation()
    {
        // -- Arrange
        {
            // Клиент
            $client1    = new Client(1, 'Джонни');
            $clientRepo = $this->makeFakeClientRepository($client1);

            //Типы доствки
            $expressDelivery = new DeliveryType(1, 'Экспресс доставка', 2);
            $regularDelivery = new DeliveryType(2, 'Обычная доставка');

            // Stub репозитория типов доставки
            $deliveryTypeRepo = $this->prophesize(DeliveryTypeRepository::class);
            $deliveryTypeRepo
                ->getById($regularDelivery->getId())
                ->willReturn($regularDelivery);

            // Виды траспорта
            $groundTransportType = new TransportType(1, 'Наземный транспорт', 50);
            $groundTransportType->addDeliveryType($expressDelivery);
            $groundTransportType->addDeliveryType($regularDelivery);

            $airTransportType = new TransportType(2, 'Воздушный транспорт');
            $airTransportType->addDeliveryType($expressDelivery);

            $waterTransport = new TransportType(3, 'Водный транспорт');
            $waterTransport->addDeliveryType($expressDelivery);
            $waterTransport->addDeliveryType($regularDelivery);


            $railwayTransport = new TransportType(4, 'Железнодорожный транспорт', null, $groundTransportType);
            $railwayTransport->addDeliveryType($regularDelivery);

            $shippingTransport = new TransportType(5, 'Судоперевозки', null, $waterTransport);
            $shippingTransport->addDeliveryType($regularDelivery);

            $international = new TransportType(3, 'Международные', null, $airTransportType);


            // Модель транспорта
            $transportModel1    = new TransportModel(1, 'Турбо Пушка', 20, $railwayTransport, 100);
            $transportModelRepo = $this->makeFakeTransportModelRepository($transportModel1);

            $contractsRepo = $this->prophesize(DeliveryContractsRepository::class);
            $contractsRepo
                ->getForTransportModel($transportModel1->getId(), '2020-01-01 17:30')
                ->willReturn([]);

            $contractsRepo
                ->getCountByClientId($client1->getId())
                ->willReturn(0);

            // Запрос на новую доставку
            $deliveryRequest = new DeliveryRequest($client1->getId(), $transportModel1->getId(), '2020-01-01 17:30', 'Нью-Йорк', $regularDelivery->getId());

            // Операция заключения договора на доставку
            $deliveryContractOperation = new DeliveryContractOperation($contractsRepo->reveal(), $clientRepo, $transportModelRepo, $deliveryTypeRepo->reveal());
        }

        // -- Act
        $response = $deliveryContractOperation->execute($deliveryRequest);

        // -- Assert
        $this->assertEmpty($response->getErrors());
        $this->assertInstanceOf(DeliveryContract::class, $response->getDeliveryContract());

        $this->assertEquals(5000, $response->getDeliveryContract()->getPrice());
    }

    /**
     * Если исли не верный тип доствки, то нельзя заключить контракт
     */
    public function test_wrongDeliveryType_failedWithOverlapInfo()
    {
        // -- Arrange
        {
            // Клиенты
            $client2    = new Client(2, 'Роберт');
            $clientRepo = $this->makeFakeClientRepository($client2);

            //Типы доствки
            $expressDelivery = new DeliveryType(1, 'Экспресс доставка', 2);
            $regularDelivery = new DeliveryType(2, 'Обычная доставка');

            // Stub репозитория типов доставки
            $deliveryTypeRepo = $this->prophesize(DeliveryTypeRepository::class);
            $deliveryTypeRepo
                ->getById($expressDelivery->getId())
                ->willReturn($expressDelivery);

            // Виды траспорта
            $groundTransportType = new TransportType(1, 'Наземный транспорт', 50);
            $groundTransportType->addDeliveryType($expressDelivery);
            $groundTransportType->addDeliveryType($regularDelivery);

            $airTransportType = new TransportType(2, 'Воздушный транспорт');
            $airTransportType->addDeliveryType($expressDelivery);

            $waterTransport = new TransportType(3, 'Водный транспорт');
            $waterTransport->addDeliveryType($expressDelivery);
            $waterTransport->addDeliveryType($regularDelivery);


            $railwayTransport = new TransportType(4, 'Железнодорожный транспорт', null, $groundTransportType);
            $railwayTransport->addDeliveryType($regularDelivery);

            $shippingTransport = new TransportType(5, 'Судоперевозки', null, $waterTransport);
            $shippingTransport->addDeliveryType($regularDelivery);

            $international = new TransportType(3, 'Международные', null, $airTransportType);

            // Модель транспорта
            $transportModel1 = new TransportModel(1, 'Турбо Пушка', 20, $railwayTransport, 100);

            $transportModelsRepo = $this->makeFakeTransportModelRepository($transportModel1);

            // Stub репозитория договоров
            $contractsRepo = $this->prophesize(DeliveryContractsRepository::class);
            $contractsRepo
                ->getForTransportModel($transportModel1->getId(), '2020-01-01 10:00')
                ->willReturn([]);

            $contractsRepo
                ->getCountByClientId($client2->getId())
                ->willReturn(0);

            // Запрос на новую доставку. 
            $deliveryRequest = new DeliveryRequest($client2->getId(), $transportModel1->getId(), '2020-01-01 10:00', 'Нью-Йорк', $expressDelivery->getId());

            // Операция заключения договора на доставку
            $deliveryContractOperation = new DeliveryContractOperation($contractsRepo->reveal(), $clientRepo, $transportModelsRepo, $deliveryTypeRepo->reveal());
        }

        // -- Act
        $response = $deliveryContractOperation->execute($deliveryRequest);

        // -- Assert
        $this->assertCount(1, $response->getErrors());

        $message = 'Указанный вид доставки не доступен для данного вида трансторта';
        $this->assertStringContainsString($message, $response->getErrors()[0]);
    }

    /**
     * Скидка 20% на каждую вторую доставку
     */
    public function test_clientEntitledToDiscount()
    {
        // -- Arrange
        {
            // Клиент
            $client1    = new Client(1, 'Джонни');
            $clientRepo = $this->makeFakeClientRepository($client1);

            //Типы доствки
            $expressDelivery = new DeliveryType(1, 'Экспресс доставка', 2);
            $regularDelivery = new DeliveryType(2, 'Обычная доставка');

            // Stub репозитория типов доставки
            $deliveryTypeRepo = $this->prophesize(DeliveryTypeRepository::class);
            $deliveryTypeRepo
                ->getById($regularDelivery->getId())
                ->willReturn($regularDelivery);

            // Виды траспорта
            $groundTransportType = new TransportType(1, 'Наземный транспорт', 50);
            $groundTransportType->addDeliveryType($expressDelivery);
            $groundTransportType->addDeliveryType($regularDelivery);

            $airTransportType = new TransportType(2, 'Воздушный транспорт');
            $airTransportType->addDeliveryType($expressDelivery);

            $waterTransport = new TransportType(3, 'Водный транспорт');
            $waterTransport->addDeliveryType($expressDelivery);
            $waterTransport->addDeliveryType($regularDelivery);


            $railwayTransport = new TransportType(4, 'Железнодорожный транспорт', null, $groundTransportType);
            $railwayTransport->addDeliveryType($regularDelivery);

            $shippingTransport = new TransportType(5, 'Судоперевозки', null, $waterTransport);
            $shippingTransport->addDeliveryType($regularDelivery);

            $international = new TransportType(3, 'Международные', null, $airTransportType);


            // Модель транспорта
            $transportModel1    = new TransportModel(1, 'Турбо Пушка', 20, $railwayTransport, 100);
            $transportModelRepo = $this->makeFakeTransportModelRepository($transportModel1);

            $contractsRepo = $this->prophesize(DeliveryContractsRepository::class);
            $contractsRepo
                ->getForTransportModel($transportModel1->getId(), '2020-01-01 17:30')
                ->willReturn([]);

            $contractsRepo
                ->getCountByClientId($client1->getId())
                ->willReturn(1);

            // Запрос на новую доставку
            $deliveryRequest = new DeliveryRequest($client1->getId(), $transportModel1->getId(), '2020-01-01 17:30', 'Нью-Йорк', $regularDelivery->getId());

            // Операция заключения договора на доставку
            $deliveryContractOperation = new DeliveryContractOperation($contractsRepo->reveal(), $clientRepo, $transportModelRepo, $deliveryTypeRepo->reveal());
        }

        // -- Act
        $response = $deliveryContractOperation->execute($deliveryRequest);

        // -- Assert
        $this->assertEmpty($response->getErrors());
        $this->assertInstanceOf(DeliveryContract::class, $response->getDeliveryContract());

        $this->assertEquals(4000, $response->getDeliveryContract()->getPrice());
    }
}