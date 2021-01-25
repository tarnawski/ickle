<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\PDO\PDOFactory;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Imbo\BehatApiExtension\Context\ApiContext;

class FeatureContext extends ApiContext implements Context
{
    /** @var PDO */
    private $connection;

    public function __construct(string $dsn)
    {
        $this->connection = PDOFactory::createFromDsn($dsn);
    }

    /**
     * @BeforeScenario @cleanDB
     * @AfterScenario @cleanDB
     */
    public function cleanDB(): void
    {
        $statement = $this->connection->prepare('DELETE FROM reference;');
        $statement->execute();
    }

    /**
     * @param TableNode $table
     * @Given There are the following references:
     */
    public function thereAreTheFollowingReferences(TableNode $table): void
    {
        foreach ($table->getColumnsHash() as $row) {
            $statement = $this->connection->prepare(
                'INSERT INTO reference (identity, name, url, created_at) VALUES (:identity, :name, :url, :created_at);'
            );
            $statement->bindValue(':identity', $row['ID']);
            $statement->bindValue(':name', $row['NAME']);
            $statement->bindValue(':url', $row['URL']);
            $statement->bindValue(':created_at', $row['CREATED_AT']);
            $statement->execute();
        }
    }

    /**
     * @Then the reference count is :code
     */
    public function assertReferenceCountIs(int $count): void
    {
        $statement = $this->connection->prepare('SELECT COUNT(identity) FROM reference;');
        $statement->execute();
        $result = $statement->fetchColumn();

        if ($count !== (int) $result) {
            throw new Exception('Reference count not match.');
        }
    }
}
