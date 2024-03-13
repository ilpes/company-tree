<?php

require 'get_tree.php';

$rootCompany = new Company(
    id: 'uuid-1',
    createdAt: '2021-02-26T00:55:36.632Z',
    name: 'Webprovise Corp',
    parentId: '0',
);

$rootCompanyTravel = new Travel(
    id: 'uuid-t97',
    createdAt: '2021-02-21T21:36:36.726Z',
    employeeName: 'Ms. Percy Herzog',
    departure: 'American Samoa',
    destination: 'Trinidad and Tobago',
    price: 652.00,
    companyId: 'uuid-1',
);

$childCompany = new Company(
    id: 'uuid-2',
    createdAt: '2021-02-25T10:35:32.978Z',
    name: 'Stamm LLC',
    parentId: 'uuid-1',
);

$childCompanyTravel = new Travel(
    id: 'uuid-t99',
    createdAt: '2020-11-13T01:52:19.030Z',
    employeeName: 'Annamarie Cormier',
    departure: 'Qatar',
    destination: 'Pakistan',
    price: 119.00,
    companyId: 'uuid-2',
);

$childCompany->addTravels([$childCompanyTravel]);

$rootCompany->addTravels([$rootCompanyTravel]);
$rootCompany->addChildren([$childCompany]);

if ($rootCompany->getCost() === 771.0) {
    echo 'Test passed!';
    exit(0);
}

echo 'Test failed!';
exit(1);
