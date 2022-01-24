<?php
declare(strict_types = 1);

namespace CargoExpress\Delivery;

use CargoExpress\Client;
use CargoExpress\ClientsRepository;
use CargoExpress\TransportModel;
use CargoExpress\TransportModelsRepository;
use DateTime;

class DeliveryContractOperation
{
    // сделал контстантой для экономии времени, в реальном проекте должно быть вынесено в отдельную сущность
    const PATH_DISTANCES = [
        'Нью-Йорк' => 250,
        'Москва' => 50
    ];

    const DISCOUNT = 0.2;

    /**
     * @var DeliveryContractsRepository
     */
    protected $contractsRepository;

    /**
     * @var ClientsRepository
     */
    protected $clientsRepository;

    /**
     * @var TransportModelsRepository
     */
    protected $transportModelsRepository;

    /**
     * @var DeliveryTypeRepository
     */
    protected $deliveryTypeRepository;

    /**
     * DeliveryContractOperation constructor.
     *
     * @param DeliveryContractsRepository $contractsRepo
     * @param ClientsRepository $clientsRepo
     * @param TransportModelsRepository $transportModelsRepo
     * @param DeliveryTypeRepository $deliveryTypeRepo
     */
    public function __construct(DeliveryContractsRepository $contractsRepo, ClientsRepository $clientsRepo, TransportModelsRepository $transportModelsRepo, DeliveryTypeRepository $deliveryTypeRepo)
    {
        $this->contractsRepository       = $contractsRepo;
        $this->clientsRepository         = $clientsRepo;
        $this->transportModelsRepository = $transportModelsRepo;
        $this->deliveryTypeRepository    = $deliveryTypeRepo;
    }

    /**
     * @param DeliveryRequest $request
     * @return DeliveryResponse
     */
    public function execute(DeliveryRequest $request): DeliveryResponse
    {
        $transportModel = $this->transportModelsRepository->getById($request->transportModelId);
        $deliveryType = $this->deliveryTypeRepository->getById($request->deliveryTypeId);

        $response = $this->validate($request, $transportModel, $deliveryType);

        if ($response) {
            return $response;
        }

        $response = new DeliveryResponse();
        
        $client = $this->clientsRepository->getById($request->clientId);

        $distance = self::PATH_DISTANCES[$request->toAddress];

        $price = $this->calculatePrice($distance, $transportModel, $deliveryType, $client);
        
        $endDate = $this->calculateEndDate($transportModel, $distance, $request->startDate);

        $contract = new DeliveryContract($client, $transportModel, $request->startDate, $price, $endDate);

        $response->setDeliveryContract($contract);

        return $response;
    }

    /**
     * @param DeliveryRequest $request
     * @param TransportModel $transportModel
     * @param DeliveryType $deliveryType
     * @return DeliveryResponse
     */
    private function validate(DeliveryRequest $request, TransportModel $transportModel, DeliveryType $deliveryType): ?DeliveryResponse
    {
        $response = new DeliveryResponse();

        $contracts = $this->contractsRepository->getForTransportModel($request->transportModelId, $request->startDate);
        
        if (count($contracts) > 0) {
            $response->pushError('Извините ' . $transportModel->getName() . ' занята ' . $request->startDate);
        }

        $transportType = $transportModel->getTransportType();

        $deliveryTypeExisted = array_filter($transportType->getDeliveryTypes(), function(DeliveryType $value, int $key) use ($deliveryType) {
            return $value->getId() == $deliveryType->getId();
        }, ARRAY_FILTER_USE_BOTH);

        if (count($deliveryTypeExisted) == 0) {
            $response->pushError('Указанный вид доставки не доступен для данного вида трансторта');
        }

        if (count($response->getErrors()) > 0) {
            return $response;
        }

        return null;
    }

    /**
     * @param int $distance
     * @param TransportModel $transportModel
     * @param DeliveryType $deliveryType
     * @param Client $client
     * @return float
     */
    private function calculatePrice(int $distance, TransportModel $transportModel, DeliveryType $deliveryType, Client $client): float
    {
        $price = $transportModel->getPricePerKilometer() * $deliveryType->getCoefficient() * $distance;

        if ($this->isClientEntitledToDiscount($client)) {
            $price = $price - ($price * self::DISCOUNT);
        }

        return $price;
    }

    /**
     * @param Client $client
     * @return bool
     */
    private function isClientEntitledToDiscount(Client $client): bool
    {
        $countContracts = $this->contractsRepository->getCountByClientId($client->getId());

        if ($countContracts == 0) {
            return false;
        }

        if (($countContracts % 2) == 0) {
            return false;
        }

        return true;
    }

    /**
     * @param TransportModel $transportModel
     * @param int $distance
     * @param string $startDate
     * @return string
     */
    private function calculateEndDate(TransportModel $transportModel, int $distance, string $startDate): string
    {
        $transportType = $transportModel->getTransportType();

        $speed​​Limit = $transportType->getSpeed​​Limit();
        $speed = $speed​​Limit ? $speed​​Limit : $transportModel->getFullSpeed();

        $duration = ($distance / $speed) * 60 * 2;

        $startDate = new DateTime($startDate);
        $endDate = clone $startDate;
        $endDate->modify('+' . $duration . ' minutes');

        return $endDate->format('Y-m-d H:i');
    }
}