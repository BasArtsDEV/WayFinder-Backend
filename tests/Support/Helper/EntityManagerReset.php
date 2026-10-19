<?php

declare(strict_types=1);

namespace App\Tests\Support\Helper;

use Codeception\Lib\Interfaces\DependsOnModule;
use Codeception\Module;
use Codeception\Module\REST;
use Codeception\Module\Symfony;
use Codeception\TestInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Zenstruck\Foundry\Configuration;

class EntityManagerReset extends Module implements DependsOnModule
{
    private Symfony $symfony;
    private REST $rest;

    protected string $dependencyMessage = <<<EOF
Set Symfony and REST as dependent modules:

modules:
    enabled:
        - \App\Tests\Support\Helper\EntityManagerReset:
            depends: [Symfony, REST]
EOF;

    /**
     * Configuration in <suite>.suite.yml file.
     *
     * @var array<string, string>
     */
    protected array $config = ['base_path' => '/api'];

    /**
     * @return array<class-string<Module>, string>
     */
    public function _depends(): array
    {
        return [
            Symfony::class => $this->dependencyMessage,
            REST::class => $this->dependencyMessage,
        ];
    }

    public function _inject(Symfony $symfony, REST $rest): void
    {
        $this->symfony = $symfony;
        $this->rest = $rest;
    }

    public function _before(TestInterface $test): void
    {
        parent::_before($test);

        /** @var ManagerRegistry $doctrine */
        $doctrine = $this->symfony->grabService('doctrine');
        /** @var EntityManager $manager */
        $manager = $doctrine->getManager();
        if (!$manager->isOpen()) {
            $doctrine->resetManager();
        }
    }

    public function _after(TestInterface $test): void
    {
        parent::_after($test);
        if (Configuration::isBooted()) {
            Configuration::shutdown();
        }
    }

    /**
     * Get configuration parameter value.
     */
    protected function getBasePath(): string
    {
        return $this->_getConfig('base_path');
    }

    /**
     * Get JSON array as JSON string.
     *
     * @param array<string, mixed> $json
     */
    protected static function arrayAsJsonString(array $json): string
    {
        return json_encode($json, JSON_UNESCAPED_SLASHES);
    }

    /**
     * Get short class name from fully qualified class name.
     */
    protected function getShortClassName(string $className): ?string
    {
        try {
            return new \ReflectionClass($className)->getShortName();
        } catch (\ReflectionException) {
            $this->fail('Unable to get short class name from fully qualified class name '.$className);
        }
    }

    /**
     * Normalize list of properties to be JSON-LD valid.
     *
     * @param array<string, string> $propertiesTypes properties to normalize
     *
     * @return array<string, string> JSON-LD normalized properties
     */
    protected static function getJsonLdPropertiesTypes(array $propertiesTypes): array
    {
        return array_merge([
            '@context' => 'string',
            '@id' => 'string',
            '@type' => 'string',
        ], $propertiesTypes);
    }

    /**
     * Assert that the JSON response is an entity.
     */
    public function seeResponseIsAnEntity(string $className, string $entityPath): void
    {
        $entityName = $this->getShortClassName($className);

        $this->rest->seeResponseContainsJson([
            '@context' => $this->getBasePath().'/contexts/'.$entityName,
            '@type' => $entityName,
            '@id' => $entityPath,
        ]);
    }

    /**
     * Assert that the JSON response is an entity.
     *
     * @param array<string, string> $propertiesTypes
     * @param array<string, mixed>  $values
     */
    public function seeResponseIsAnItem(array $propertiesTypes, array $values = []): void
    {
        $jsonResponse = $this->grabJsonResponse();
        $jsonLdPropertiesTypes = self::getJsonLdPropertiesTypes($propertiesTypes);

        $this->rest->seeResponseMatchesJsonType($jsonLdPropertiesTypes);

        // Check if JSON response contains each expected property
        foreach ($jsonLdPropertiesTypes as $property => $type) {
            // Property in JSON response ?
            self::assertArrayHasKey($property, $jsonResponse, 'property '.$property.' not found in '.self::arrayAsJsonString($jsonResponse));
            // Check value of property in JSON response ?
            if (isset($values[$property])) {
                self::assertSame($values[$property], $jsonResponse[$property]);
            }
        }
        // Check if JSON response contains unexpected properties
        foreach ($jsonResponse as $property => $value) {
            self::assertArrayHasKey($property, $jsonLdPropertiesTypes, 'property '.$property.' not expected in '.self::arrayAsJsonString($jsonResponse));
        }
        // Check if each expected value is in JSON response
        foreach ($values as $property => $value) {
            self::assertArrayHasKey($property, $jsonResponse, 'property '.$property.' expected in '.self::arrayAsJsonString($jsonResponse));
        }
    }

    /**
     * Assert that the JSON response is collection.
     *
     * @param array<string, string> $propertiesTypes
     */
    public function seeResponseIsACollection(string $className, string $collectionPath, array $propertiesTypes = []): void
    {
        $entityName = $this->getShortClassName($className);
        $this->rest->seeResponseMatchesJsonType(self::getJsonLdPropertiesTypes($propertiesTypes));
        $this->rest->seeResponseContainsJson([
            '@context' => $this->getBasePath().'/contexts/'.$entityName,
            '@type' => 'Collection',
            '@id' => $collectionPath,
        ]);
    }

    /**
     * Grab JSON response.
     *
     * @return array<string, mixed>|null
     */
    public function grabJsonResponse(): ?array
    {
        try {
            return $this->rest->grabDataFromResponseByJsonPath('$')[0];
        } catch (\Exception) {
            $this->fail('Response is not JSON');
        }
    }
}
