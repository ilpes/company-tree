<?php

class Travel
{
    public function __construct(
        public readonly string $id,
        public readonly string $createdAt,
        public readonly string $employeeName,
        public readonly string $departure,
        public readonly string $destination,
        public readonly float $price,
        public readonly string $companyId,
    ) {}

    public static function from(array $data): ?Travel
    {
        try {
            return new Travel(
                id: $data['id'],
                createdAt: $data['createdAt'],
                employeeName: $data['employeeName'],
                departure: $data['departure'],
                destination: $data['destination'],
                price: (float)$data['price'],
                companyId: $data['companyId'],
            );
        } catch (Throwable) {
            return null;
        }
    }
}

class Company implements JsonSerializable
{
    /**
     * @var Travel[]
     */
    private array $travels = [];

    /**
     * @var Company[]
     */
    private array $children = [];

    public function __construct(
        public readonly string $id,
        public readonly string $createdAt,
        public readonly string $name,
        public readonly string $parentId,
    ) {}

    public function getTravelCosts(): float
    {
        return array_reduce($this->travels, fn(mixed $carry, Travel $travel) => $carry + $travel->price) ?? 0;
    }

    private function getChildrenTravelCost(): float
    {
        return array_reduce($this->children, fn(mixed $carry, Company $company) => $carry + $company->getCost()) ?? 0;
    }


    public function getCost(): float
    {
        return $this->getTravelCosts() + $this->getChildrenTravelCost();
    }

    /**
     * @param Travel[] $travels
     */
    public function addTravels(array $travels): void
    {
        array_push($this->travels, ... $travels);
    }

    /**
     * @param Company[] $children
     */
    public function addChildren(array $children): void
    {
        array_push($this->children, ... $children);
    }

    public function jsonSerialize(): mixed
    {
        return [
            'id' => $this->id,
            'createdAt' => $this->createdAt,
            'name' => $this->name,
            'parentId' => $this->parentId,
            'cost' => $this->getCost(),
            'children' => $this->children,
        ];
    }

    public static function from(array $data): ?Company
    {
        try {
            return new Company(
                id: $data['id'],
                createdAt: $data['createdAt'],
                name: $data['name'],
                parentId: $data['parentId'],
            );
        } catch (Throwable) {
            return null;
        }
    }
}

class TestScript
{
    /**
     * @var string
     */
    private const COMPANY_API_URL = 'https://5f27781bf5d27e001612e057.mockapi.io/webprovise/companies';

    /**
     * @var string
     */
    private const TRAVEL_API_URL = 'https://5f27781bf5d27e001612e057.mockapi.io/webprovise/travels';

    /**
     * @var Company[]
     */
    private array $companies;

    /**
     * @var Travel[]
     */
    private array $travels;

    /**
     * @var Travel[]
     */
    private function fetchTravels(): array
    {
        $content = file_get_contents(self::TRAVEL_API_URL);

        if ($content === false) {
            throw new RuntimeException('Could not fetch travel list');
        }

        $travelsData = json_decode($content, true);

        if ($travelsData === false) {
            throw new RuntimeException('Could not parse the travel list');
        }

        $travelsData = array_map(function (array $travelData) {
            return Travel::from($travelData);
        }, $travelsData);

        // The $travels array may contain null values e.g. when the json data is in an invalid format
        // hence the need to filter out null vales
        return $this->travels = array_filter($travelsData);
    }

    /**
     * @var Travel[]
     */
    private function getTravels(Company $company): array
    {
        $travels = $this->travels ?? $this->fetchTravels();

        return array_filter($travels, fn(Travel $travel) => $travel->companyId === $company->id);
    }

    /**
     * @return Company[]
     */
    private function fetchCompanies(): array
    {
        $content = file_get_contents(self::COMPANY_API_URL);

        if ($content === false) {
            throw new RuntimeException('Could not fetch company list');
        }

        $companiesData = json_decode($content, true);

        if ($companiesData === false) {
            throw new RuntimeException('Could not parse the company list');
        }

        $companies = array_map(function (array $companyData) {
            $company = Company::from($companyData);
            $company?->addTravels($this->getTravels($company));

            return $company;
        }, $companiesData);

        // The $companies array may contain null values e.g. when the json data is in an invalid format
        // hence the need to filter out null vales
        $companies = array_filter($companies);

        return $this->companies = $this->getCompanyTree($companies);
    }

    /**
     * @param Company[] $companies
     * @param string|null $parentId
     *
     * @return Company[]
     */
    private function getCompanyTree(array &$companies, string $parentId = null): array
    {
        $tree = [];

        foreach ($companies as $index => &$company) {

            if ($parentId === null || $company->parentId === $parentId) {
                $children = $this->getCompanyTree($companies, $company->id);
                $company->addChildren($children);

                $tree[] = $company;
                unset($companies[$index]);
            }
        }

        return $tree;
    }

    /**
     * @return Company[]
     */
    private function getCompanies(): array
    {
        return $this->companies ?? $this->fetchCompanies();
    }

    public function execute(): void
    {
        $start = microtime(true);
        echo json_encode($this->getCompanies(), JSON_PRETTY_PRINT);
        echo 'Total time: '.  (microtime(true) - $start);
    }
}

(new TestScript())->execute();