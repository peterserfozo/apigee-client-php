<?php

/*
 * Copyright 2018 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      https://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Apigee\Edge\Controller;

use Apigee\Edge\Api\Management\Controller\OrganizationController;
use Apigee\Edge\Api\Management\Controller\OrganizationControllerInterface;
use Apigee\Edge\ClientInterface;
use Apigee\Edge\Exception\CpsNotEnabledException;
use Apigee\Edge\Structure\CpsListLimitInterface;

/**
 * Class CpsLimitEntityController.
 *
 * @see \Apigee\Edge\Controller\CpsLimitEntityControllerInterface
 */
abstract class CpsLimitEntityController extends EntityController implements CpsLimitEntityControllerInterface
{
    /** @var \Apigee\Edge\Api\Management\Controller\OrganizationControllerInterface */
    protected $organizationController;

    /**
     * CpsLimitEntityController constructor.
     *
     * @param string $organization
     * @param \Apigee\Edge\ClientInterface $client
     * @param \Symfony\Component\Serializer\Normalizer\NormalizerInterface[]|\Symfony\Component\Serializer\Normalizer\DenormalizerInterface[] $entityNormalizers
     * @param OrganizationControllerInterface|null $organizationController
     */
    public function __construct(
        string $organization,
        ClientInterface $client,
        array $entityNormalizers = [],
        OrganizationControllerInterface $organizationController = null
    ) {
        parent::__construct($organization, $client, $entityNormalizers);
        $this->organizationController = $organizationController ?: new OrganizationController($client);
    }

    /**
     * Creates a CPS limit if it is supported on the organization.
     *
     * @param int $limit
     *   Number of items to return. Default is 0 which means load as much as
     *   supported. (Different endpoints have different limits, ex.:
     *   1000 for API products, 100 for Company apps.)
     * @param null|string $startKey
     *   First item in the list, if it is not set then Apigee Edge decides the
     *   first item.
     *
     * @throws \Apigee\Edge\Exception\CpsNotEnabledException
     *   If CPS listing is not supported on the organization.
     *
     * @return CpsListLimitInterface
     *   CPS limit object.
     */
    public function createCpsLimit(int $limit = 0, ?string $startKey = null): CpsListLimitInterface
    {
        /** @var \Apigee\Edge\Api\Management\Entity\OrganizationInterface $organization */
        $organization = $this->organizationController->load($this->organization);
        if (!$organization->getPropertyValue('features.isCpsEnabled')) {
            throw new CpsNotEnabledException($this->organization);
        }

        // Create an anonymous class here because this class should not exist and be in use
        // in those controllers that do not work with entities that belongs to an organization.
        $cpsLimit = new class() implements CpsListLimitInterface {
            protected $startKey;

            protected $limit;

            /**
             * @inheritdoc
             */
            public function getStartKey(): ?string
            {
                return $this->startKey;
            }

            /**
             * @inheritdoc
             */
            public function getLimit(): int
            {
                return $this->limit;
            }

            /**
             * @inheritdoc
             */
            public function setStartKey(?string $startKey): ?string
            {
                $this->startKey = $startKey;

                return $this->startKey;
            }

            /**
             * @inheritdoc
             */
            public function setLimit(int $limit): int
            {
                $this->limit = $limit;

                return $this->limit;
            }
        };

        $cpsLimit->setLimit($limit);
        $cpsLimit->setStartKey($startKey);

        return $cpsLimit;
    }
}
