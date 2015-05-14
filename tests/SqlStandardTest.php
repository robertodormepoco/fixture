<?php
 
/**
 * Author: Roberto Lombi <roberto.lombi@immobiliare.it>
 * Date: 14/05/15
 * Time: 11:14
 *
 * Copyright © Immobiliare S.p.A 2015 All rights reserved.
 * No part of this product may be reproduced without Immobiliare S.p.A. express consent.
 */

namespace Codesleeve\Fixture;

use Codesleeve\Fixture\Drivers\SqlStandard;
use PHPUnit_Framework_TestCase;
use Mockery as m;
use PDO;

class SqlStandardTest extends PHPUnit_Framework_TestCase
{
    /**
     * An instance of the fixture class.
     *
     * @var Fixture
     */
    protected $fixture;

    /**
     * A PDO instance.
     *
     * @var PDO
     */
    protected $db;

    /**
     * setUp method.
     */
    public function setUp()
    {
        $this->buildFixture();
    }

    /**
     * tearDown method.
     */
    public function tearDown()
    {
        $this->db->query("DELETE FROM users");
        $this->fixture->setFixtures(array());
        m::close();
    }

    /**
     * Test that the up method will populate all fixtures when called
     * with an empty parameter list.
     *
     * @test
     * @return void
     */
    public function itShouldPopulateAllFixtures()
    {
        $this->fixture->setConfig(array('location' => __DIR__ . '/fixtures/sqlstandard'));
        $this->fixture->up();

        list($userCount) = $this->getRecordCounts();

        $this->assertEquals('Roberto', $this->fixture->users('Roberto')->first_name);
        $this->assertEquals(4, $userCount);
        $this->assertCount(1, $this->fixture->getFixtures());
    }

    /**
     * Test that the up method will only populate fixtures that
     * are supplied to it via parameters.
     *
     * @test
     * @return void
     */
    public function itShouldPopulateOnlySomeFixtures()
    {
        $this->fixture->setConfig(array('location' => __DIR__ . '/fixtures/sqlstandard'));
        $this->fixture->up(array('users'));

        list($userCount) = $this->getRecordCounts();

        $this->assertEquals('Roberto', $this->fixture->users('Roberto')->first_name);
        $this->assertEquals(4, $userCount);
        $this->assertCount(1, $this->fixture->getFixtures());
    }

    /**
     * Test that the down method will truncate all current fixture table data
     * and empty the fixtures array.
     *
     * @test
     * @return void
     */
    public function itShouldTruncateAllFixtures()
    {
        $this->fixture->setConfig(array('location' => __DIR__ . '/fixtures/sqlstandard'));
        $this->fixture->up();
        $this->fixture->down();

        list($userCount) = $this->getRecordCounts();

        $this->assertEmpty($this->fixture->getFixtures());
        $this->assertEquals(0, $userCount);
    }

    /**
     * Build a fixture instance.
     *
     * @return void
     */
    protected function buildFixture()
    {
        if ($this->fixture) {
            return;
        }

        $this->db = $this->buildDB();

        $this->fixture = Fixture::getInstance();
        $repository = new SqlStandard($this->db);
        $this->fixture->setDriver($repository);
    }

    /**
     * Helper method to build a PDO instance.
     *
     * @return PDO
     */
    protected function buildDB()
    {
        $db = new PDO('mysql:host=localhost;dbname=test', 'dev', 'my-dev-pass');
        $db->exec("CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTO_INCREMENT, first_name TEXT, last_name TEXT)");

        return $db;
    }

    /**
     * Helper method to return the current record count in each
     * fixture table.
     *
     * @return array
     */
    protected function getRecordCounts()
    {
        $userQuery = $this->db->query('SELECT COUNT(*) AS count from users');
        $userCount = $userQuery->fetchColumn(0);

        return array($userCount);
    }

    /**
     * Helper method to return the current record count in each
     * fixture table.
     *
     * @return array
     */
    protected function getUsers()
    {
        $userQuery = $this->db->query('SELECT * from users');
        $users = $userQuery->fetchAll(PDO::FETCH_ASSOC);

        return $users;
    }


}