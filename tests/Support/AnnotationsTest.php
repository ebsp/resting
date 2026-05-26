<?php

namespace Seier\Resting\Tests\Support;

use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollection;
use Seier\Resting\Annotations\Doc;
use Seier\Resting\Annotations\Lists;
use Seier\Resting\Support\OpenAPI;
use Seier\Resting\Tests\Meta\AnnotatedRoutes;
use Seier\Resting\Tests\Meta\DocumentedResource;
use Seier\Resting\Tests\Meta\PersonResource;
use Seier\Resting\Tests\Meta\UnionResourceA;
use Seier\Resting\Tests\Meta\UnionResourceB;
use Seier\Resting\Tests\Meta\UnionResourceBase;
use Seier\Resting\Tests\TestCase;

class AnnotationsTest extends TestCase
{
    public function testDocAttributeAcceptsString()
    {
        $doc = new Doc('a single paragraph');

        $this->assertSame(['a single paragraph'], $doc->paragraphs);
    }

    public function testDocAttributeAcceptsArrayOfStrings()
    {
        $doc = new Doc(['first', 'second', 'third']);

        $this->assertSame(['first', 'second', 'third'], $doc->paragraphs);
    }

    public function testDocAttributeRejectsNonStringValues()
    {
        $this->expectException(\InvalidArgumentException::class);

        new Doc([123]);
    }

    public function testListsAttributeStoresResourceClass()
    {
        $lists = new Lists(PersonResource::class);

        $this->assertSame(PersonResource::class, $lists->resource);
    }

    public function testListsAttributeRejectsNonResourceClass()
    {
        $this->expectException(\InvalidArgumentException::class);

        new Lists(\stdClass::class);
    }

    public function testDocOnResourceClassBecomesSchemaDescription()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add(new Route(['POST'], 'documented', fn (DocumentedResource $r) => null));

        $openAPI = new OpenAPI($routeCollection);
        $schema = $openAPI->toArray();

        $component = $schema['components']['schemas'][OpenAPI::resourceRefName(DocumentedResource::class)];

        $this->assertArrayHasKey('description', $component);
        $this->assertSame(DocumentedResource::CLASS_DOC, $component['description']);
    }

    public function testDocOnResourceFieldBecomesPropertyDescription()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add(new Route(['POST'], 'documented', fn (DocumentedResource $r) => null));

        $openAPI = new OpenAPI($routeCollection);
        $schema = $openAPI->toArray();

        $properties = $schema['components']['schemas'][OpenAPI::resourceRefName(DocumentedResource::class)]['properties'];

        $this->assertArrayHasKey('description', $properties['name']);
        $this->assertStringContainsString(DocumentedResource::NAME_FIELD_DOC, $properties['name']['description']);
    }

    public function testDocOnFieldSupportsMultipleParagraphs()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add(new Route(['POST'], 'documented', fn (DocumentedResource $r) => null));

        $openAPI = new OpenAPI($routeCollection);
        $schema = $openAPI->toArray();

        $properties = $schema['components']['schemas'][OpenAPI::resourceRefName(DocumentedResource::class)]['properties'];

        $this->assertArrayHasKey('description', $properties['age']);
        $this->assertStringContainsString(DocumentedResource::AGE_FIELD_DOC_FIRST, $properties['age']['description']);
        $this->assertStringContainsString(DocumentedResource::AGE_FIELD_DOC_SECOND, $properties['age']['description']);
        $this->assertStringContainsString("\n\n", $properties['age']['description']);
    }

    public function testStackedDocOnFieldConcatenatesAllParagraphs()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add(new Route(['POST'], 'documented', fn (DocumentedResource $r) => null));

        $openAPI = new OpenAPI($routeCollection);
        $schema = $openAPI->toArray();

        $properties = $schema['components']['schemas'][OpenAPI::resourceRefName(DocumentedResource::class)]['properties'];

        $this->assertArrayHasKey('description', $properties['stackedDocs']);
        $description = $properties['stackedDocs']['description'];

        $this->assertStringContainsString(DocumentedResource::STACKED_FIELD_DOC_FIRST, $description);
        $this->assertStringContainsString(DocumentedResource::STACKED_FIELD_DOC_SECOND, $description);
        $this->assertLessThan(
            strpos($description, DocumentedResource::STACKED_FIELD_DOC_SECOND),
            strpos($description, DocumentedResource::STACKED_FIELD_DOC_FIRST),
            'Stacked Doc attributes should preserve declaration order.'
        );
    }

    public function testUndocumentedFieldHasNoDocDescription()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add(new Route(['POST'], 'documented', fn (DocumentedResource $r) => null));

        $openAPI = new OpenAPI($routeCollection);
        $schema = $openAPI->toArray();

        $properties = $schema['components']['schemas'][OpenAPI::resourceRefName(DocumentedResource::class)]['properties'];

        if (isset($properties['undescribed']['description'])) {
            $this->assertStringNotContainsString(DocumentedResource::NAME_FIELD_DOC, $properties['undescribed']['description']);
            $this->assertStringNotContainsString(DocumentedResource::AGE_FIELD_DOC_FIRST, $properties['undescribed']['description']);
        }
    }

    public function testDocOnEndpointMethodBecomesEndpointDescription()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add(new Route(['GET'], 'described', AnnotatedRoutes::describedEndpoint(...)));

        $openAPI = new OpenAPI($routeCollection);
        $schema = $openAPI->toArray();

        $this->assertSame(
            AnnotatedRoutes::DESCRIBED_ENDPOINT_DOC,
            $schema['paths']['/described']['get']['description']
        );
    }

    public function testDocOnEndpointSupportsMultipleParagraphs()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add(new Route(['GET'], 'multi', AnnotatedRoutes::multiParagraphEndpoint(...)));

        $openAPI = new OpenAPI($routeCollection);
        $schema = $openAPI->toArray();

        $description = $schema['paths']['/multi']['get']['description'];
        $this->assertSame(
            AnnotatedRoutes::MULTI_PARAGRAPH_DOC_FIRST . "\n\n" . AnnotatedRoutes::MULTI_PARAGRAPH_DOC_SECOND,
            $description
        );
    }

    public function testDocOnRequestBodyParameterBecomesRequestBodyDescription()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add(new Route(['POST'], 'create', AnnotatedRoutes::annotatedRequestBody(...)));

        $openAPI = new OpenAPI($routeCollection);
        $schema = $openAPI->toArray();

        $this->assertSame(
            AnnotatedRoutes::REQUEST_BODY_PARAM_DOC,
            $schema['paths']['/create']['post']['requestBody']['description']
        );
    }

    public function testDocOnScalarUriParameterBecomesPathParameter()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add(new Route(['GET'], 'users/{id}', AnnotatedRoutes::pathParamEndpoint(...)));

        $openAPI = new OpenAPI($routeCollection);
        $schema = $openAPI->toArray();

        $parameters = $schema['paths']['/users/{id}']['get']['parameters'] ?? [];

        $matched = null;
        foreach ($parameters as $parameter) {
            if (($parameter['name'] ?? null) === 'id' && ($parameter['in'] ?? null) === 'path') {
                $matched = $parameter;
                break;
            }
        }

        $this->assertNotNull($matched, 'Expected a path parameter named "id"');
        $this->assertTrue($matched['required']);
        $this->assertSame(AnnotatedRoutes::PATH_PARAM_DOC, $matched['description']);
        $this->assertSame('integer', $matched['schema']['type']);
    }

    public function testListsAttributeRegistersResourcesInSchema()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add(new Route(['GET'], 'lists-persons', AnnotatedRoutes::listsPersons(...)));

        $openAPI = new OpenAPI($routeCollection);
        $schema = $openAPI->toArray();

        $this->assertArrayHasKey(
            OpenAPI::resourceRefName(PersonResource::class),
            $schema['components']['schemas']
        );
    }

    public function testListsAttributeSupportsUnionInheritance()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add(new Route(['POST'], 'lists-union', AnnotatedRoutes::listsUnionBase(...)));

        $openAPI = new OpenAPI($routeCollection);
        $schema = $openAPI->toArray();

        $this->assertArrayNotHasKey(
            OpenAPI::resourceRefName(UnionResourceBase::class),
            $schema['components']['schemas']
        );
        $this->assertArrayHasKey(
            OpenAPI::resourceRefName(UnionResourceA::class),
            $schema['components']['schemas']
        );
        $this->assertArrayHasKey(
            OpenAPI::resourceRefName(UnionResourceB::class),
            $schema['components']['schemas']
        );
    }

    public function testEndpointWithoutDocHasEmptyDescription()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add(new Route(['GET'], 'no-doc', fn () => null));

        $openAPI = new OpenAPI($routeCollection);
        $schema = $openAPI->toArray();

        $this->assertSame('', $schema['paths']['/no-doc']['get']['description']);
    }

    public function testStackedDocOnEndpointConcatenatesAllParagraphs()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add(new Route(['GET'], 'stacked', AnnotatedRoutes::stackedDocEndpoint(...)));

        $openAPI = new OpenAPI($routeCollection);
        $schema = $openAPI->toArray();

        $description = $schema['paths']['/stacked']['get']['description'];

        $this->assertSame(
            implode("\n\n", [
                AnnotatedRoutes::STACKED_ENDPOINT_DOC_FIRST,
                AnnotatedRoutes::STACKED_ENDPOINT_DOC_SECOND,
                AnnotatedRoutes::STACKED_ENDPOINT_DOC_THIRD,
            ]),
            $description
        );
    }

    public function testStackedDocOnParameterConcatenatesAllParagraphs()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->add(new Route(['GET'], 'stacked/{id}', AnnotatedRoutes::stackedDocParam(...)));

        $openAPI = new OpenAPI($routeCollection);
        $schema = $openAPI->toArray();

        $parameters = $schema['paths']['/stacked/{id}']['get']['parameters'] ?? [];

        $matched = null;
        foreach ($parameters as $parameter) {
            if (($parameter['name'] ?? null) === 'id' && ($parameter['in'] ?? null) === 'path') {
                $matched = $parameter;
                break;
            }
        }

        $this->assertNotNull($matched);
        $this->assertSame(
            AnnotatedRoutes::STACKED_PARAM_DOC_FIRST . "\n\n" . AnnotatedRoutes::STACKED_PARAM_DOC_SECOND,
            $matched['description']
        );
    }

    public function testDocHelperReadsRepeatedAttributesInDeclarationOrder()
    {
        $reflection = new \ReflectionMethod(AnnotatedRoutes::class, 'stackedDocEndpoint');

        $paragraphs = Doc::paragraphsFor($reflection);

        $this->assertSame(
            [
                AnnotatedRoutes::STACKED_ENDPOINT_DOC_FIRST,
                AnnotatedRoutes::STACKED_ENDPOINT_DOC_SECOND,
                AnnotatedRoutes::STACKED_ENDPOINT_DOC_THIRD,
            ],
            $paragraphs
        );
    }
}
