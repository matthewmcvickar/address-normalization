<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use ZeroDaHero\Normalizer;
use ZeroDaHero\Address;
use ZeroDaHero\SimpleAddress;

class NormalizerTest extends TestCase
{
    /** @test */
    public function testReturnsAddressClass()
    {
        $normalizer = new Normalizer();
        $address = $normalizer->parse('1234 Main St SE, Minneapolis, MN 55401');

        $this->assertInstanceOf(Address::class, $address);
    }

    /** @test */
    public function testReturnsAddressOnFivePart()
    {
        $normalizer = new Normalizer();
        $address = $normalizer->parseFromComponents('1234 Main St SE', null, 'Minneapolis', 'MN', '55401');

        $this->assertInstanceOf(Address::class, $address);
    }

    /** @test */
    public function testReturnsSimpleAddressOnBadFivePart()
    {
        $normalizer = new Normalizer(['strict_mode' => false]);
        $address = $normalizer->parseFromComponents('1234 Main St SE, Unit 301 Unit 301', null, 'Minneapolis,', 'MN,', '55401');

        $this->assertInstanceOf(SimpleAddress::class, $address);
    }

    /** @test */
    public function testReturnsFalseOnBadFivePartStrict()
    {
        $normalizer = new Normalizer(['strict_mode' => true]);
        $address = $normalizer->parseFromComponents('1234 Main St SE, Unit 301 Unit 301', null, 'Minneapolis,', 'MN,', '55401');

        $this->assertFalse($address);
    }

    public static function normalizesAddressesDataProvider() {
        return [
            [
                '1234 Main Street Southeast, Minneapolis, MN 55401',
                '1234 Main St. SE, Minneapolis, MN 55401'
            ],
            [
                '1234 Main St SE, Minneapolis, Minnesota 55401',
                '1234 Main St. SE, Minneapolis, MN 55401'
            ],
            [
                '1234 Main St southeast, Minneapolis, Minnesota 55401',
                '1234 Main St. SE, Minneapolis, MN 55401'
            ],
        ];
    }

    #[DataProvider('normalizesAddressesDataProvider')]
    public function testNormalizesAddresses($test, $expected_result)
    {
        $normalizer = new Normalizer();

        $this->assertEquals(
            (string)$normalizer->parse($expected_result),
            (string)$normalizer->parse($test)
        );
    }

    public static function badAddressesDataProvider() {
        return [
            // double unit no commas
            [ '1234 Main St. SE Unit 101 Unit 101' ],
            // double unit mismatch comma
            [ '1234 Main St. SE, Unit 101 Apt 101, Minneapolis, MN 55555' ],
            // double unit comma
            [ '3333 West End Ave, Unit 301 Unit 301, Nashville, TN, 37205' ],
            // nonsense
            [ 'Main Street West Fork Soup Salad' ],
        ];
    }

    /** @test */
    #[DataProvider('badAddressesDataProvider')]
    public function testFailsOnBadAddresses($address)
    {
        $normalizer = new Normalizer();

        $this->assertFalse($normalizer->parse($address));
    }

    public static function addressesWithoutUnitPrefixDataProvider() {
        return [
            [ // Test without unit prefix
                'test' => '1234 W Main Avenue 1W, Chicago, IL, 60647',
                'expected_result' => '1234 W Main Ave #1W, Chicago, IL 60647'
            ],
            [ // Regression test with unit prefix
                'test' => '1234 W Main Avenue Unit 1W, Chicago, IL, 60647',
                'expected_result' => '1234 W Main Ave Unit 1W, Chicago, IL 60647'
            ],
            [ // Regression test with unit prefix
                'test' => '1234 W Main Avenue Apartment 1W, Chicago, IL, 60647',
                'expected_result' => '1234 W Main Ave Apartment 1W, Chicago, IL 60647'
            ],
            [ // Regression test with unit prefix
                'test' => '1234 W Main Avenue #1W, Chicago, IL, 60647',
                'expected_result' => '1234 W Main Ave #1W, Chicago, IL 60647'
            ],
            [ // Regression test with unit prefix
                'test' => '1234 W Main Avenue Room 1, Chicago, IL, 60647',
                'expected_result' => '1234 W Main Ave Room 1, Chicago, IL 60647'
            ],
            [ // Regression test with unit prefix
                'test' => '1234 W Main Avenue Apt 1W, Chicago, IL, 60647',
                'expected_result' => '1234 W Main Ave Apt 1W, Chicago, IL 60647'
            ],
            [ // Regression test without any unit
                'test' => '1234 W Main Street, Chicago, IL, 60647',
                'expected_result' => '1234 W Main St, Chicago, IL 60647'
            ],
        ];
    }

    /** @test */
    #[DataProvider('addressesWithoutUnitPrefixDataProvider')]
    public function testHandlesAddressWithoutUnitPrefix($test, $expected_result)
    {
        $normalizer = new Normalizer();

        $this->assertEquals(
            $expected_result,
            (string)$normalizer->parse($test)
        );
    }

    public static function addressesWithMultiWordCityDataProvider() {
        return [
            'Two-word city; without unit; with commas' => [
                'test'            => '123 Main Street, Los Angeles, CA 90012',
                'expected_result' => '123 Main St, Los Angeles, CA 90012',
            ],
            'Three-word city; without unit; with commas' => [
                'test'            => '123 Main Street, San Luis Obispo, CA 93405',
                'expected_result' => '123 Main St, San Luis Obispo, CA 93405',
            ],
            'Multi-word city; without unit; without commas' => [
                'test'            => '123 Main Street Los Angeles CA 90012',
                'expected_result' => '123 Main St, Los Angeles, CA 90012',
            ],
            'Multi-word city; with unit prefix; with commas' => [
                'test'            => '123 Main Street Apt 14A, Los Angeles, CA 90012',
                'expected_result' => '123 Main St Apt 14A, Los Angeles, CA 90012',
            ],
            'Multi-word city; without unit prefix; with commas' => [
                'test'            => '123 Main Street 1A, Los Angeles, CA 90012',
                'expected_result' => '123 Main St #1A, Los Angeles, CA 90012',
            ],
            'Multi-word city; with unit prefix; without commas' => [
                'test'            => '123 Main Street Apt 14, Los Angeles, CA 90012',
                'expected_result' => '123 Main St Apt 14, Los Angeles, CA 90012',
            ],
            'Multi-word city; without unit prefix; without commas' => [
                'test'            => '123 Main Street 1A Los Angeles CA 90012',
                'expected_result' => '123 Main St #1A, Los Angeles, CA 90012',
            ],
            'Multi-word city; without unit prefix; unit is only a number; without commas' => [
                'test'            => '123 Main Street 1 Los Angeles CA 90012',
                'expected_result' => '123 Main St #1, Los Angeles, CA 90012',
            ],

            // This is an edge case where we can't tell whether the 'A' is part
            // of the street name or a unit. Since there's a comma, we assume
            // it's part of the street.
            'Multi-word city; without unit prefix; unit is only a letter; with commas' => [
                'test'            => '123 Main Street A, Los Angeles, CA 90012',
                'expected_result' => '123 Main Street A, Los Angeles, CA 90012',
            ],

            // This is an edge case where we can't tell whether the 'A' is part
            // of the street or the city. Since there's no comma and we've
            // found 'street,' we assume it's part of the city.
            'Multi-word city; without unit prefix; unit is only a letter; without commas' => [
                'test'            => '123 Main Street A Los Angeles CA 90012',
                'expected_result' => '123 Main St, A Los Angeles, CA 90012',
            ],
        ];
    }

    /** @test */
    #[DataProvider('addressesWithMultiWordCityDataProvider')]
    public function testHandlesAddressWithMultiWordCity($test, $expected_result)
    {
        $normalizer = new Normalizer();

        $this->assertEquals(
            $expected_result,
            (string)$normalizer->parse($test)
        );
    }
}
