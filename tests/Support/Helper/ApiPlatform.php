<?php

declare(strict_types=1);

namespace App\Tests\Support\Helper;

use Codeception\Module\REST;
use Codeception\Util\JsonType;

class ApiPlatform extends REST
{
    /**
     * Configuration in <suite>.suite.yml file.
     *
     * @var array<string, string>
     */
    protected array $config = ['base_path' => ''];

    /**
     * Executed before suite.
     *
     * @param array<string, mixed> $settings
     */
    public function _beforeSuite(array $settings = []): void
    {
        // Add custom filter to JsonType list to match paths (ex : /api/product/12)
        JsonType::addCustomFilter('path', function ($value) {
            return preg_match('@^'.$this->getBasePath().'(?:/\w+)*$@', $value);
        });
    }

    /**
     * {@inheritDoc}
     * Override to send HTTP headers.
     *
     * @param array<string, mixed>|string $params
     * @param array<string, mixed>        $files
     */
    public function sendPatch(string $url, $params = [], array $files = []): ?string
    {
        $this->haveHttpHeader('Content-Type', 'application/merge-patch+json');

        return parent::sendPatch($url, $params, $files);
    }

    /**
     * {@inheritDoc}
     * Override to send HTTP headers.
     *
     * @param array<string, mixed>|string $params
     * @param array<string, mixed>        $files
     */
    public function sendPost(string $url, $params = [], array $files = []): ?string
    {
        $this->haveHttpHeader('Content-Type', 'application/ld+json');

        return parent::sendPost($url, $params, $files);
    }

    /**
     * {@inheritDoc}
     * Override to send HTTP headers.
     *
     * @param array<string, mixed>|string $params
     * @param array<string, mixed>        $files
     */
    public function sendPut(string $url, $params = [], array $files = []): ?string
    {
        $this->haveHttpHeader('Content-Type', 'application/ld+json');

        return parent::sendPut($url, $params, $files);
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
            '@context' => 'string:path',
            '@id' => 'string:path',
            '@type' => 'string',
        ], $propertiesTypes);
    }

    /**
     * Assert that the JSON response is an entity.
     */
    public function seeResponseIsAnEntity(string $className, string $entityPath): void
    {
        $entityName = $this->getShortClassName($className);

        $this->seeResponseContainsJson([
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

        $this->seeResponseMatchesJsonType($jsonLdPropertiesTypes);

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
        $this->seeResponseMatchesJsonType(self::getJsonLdPropertiesTypes($propertiesTypes));
        $this->seeResponseContainsJson([
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
            return $this->grabDataFromResponseByJsonPath('$')[0];
        } catch (\Exception) {
            $this->fail('Response is not JSON');
        }
    }
}
