<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\PDO;

use App\Domain\Reference\Identity;
use App\Domain\Reference\Name;
use App\Domain\Reference\Reference;
use App\Domain\Reference\Url;
use App\Domain\ReferenceRepositoryInterface;
use App\Infrastructure\Exception\PersistenceException;
use DateTimeImmutable;
use PDO;
use PDOException;

class ReferenceRepository implements ReferenceRepositoryInterface
{
    private const DATE_TIME_FORMAT = 'Y-m-d H:i:s';

    private PDO $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    public function exist(Name $name): bool
    {
        try {
            $sth = $this->connection->prepare(
                'SELECT EXISTS(SELECT `identity` FROM `reference` WHERE name = :name)'
            );
            $sth->bindValue(':name', $name->asString());
            $sth->execute();
            $result = $sth->fetchColumn();
        } catch (PDOException $exception) {
            throw new PersistenceException('Failed to fetch reference by name.', 0, $exception);
        }

        return 1 === (int) $result;
    }

    public function get(Name $name): Reference
    {
        try {
            $sth = $this->connection->prepare(
                'SELECT `identity`, `name`, `url`, `created_at` FROM `reference` WHERE `name` = :name'
            );
            $sth->bindValue(':name', $name->asString());
            $sth->execute();
            $result = $sth->fetch();
        } catch (PDOException $exception) {
            throw new PersistenceException('Failed to fetch reference by name.', 0, $exception);
        }

        return Reference::fromParameters(
            Identity::fromString($result['identity']),
            Name::fromString($result['name']),
            Url::fromString($result['url']),
            new DateTimeImmutable($result['created_at']),
        );
    }

    public function add(Reference $reference): void
    {
        try {
            $sth = $this->connection->prepare(
                'INSERT INTO `reference` (`identity`, `name`, `url`, `created_at`)
                 VALUES (:identity, :name, :url, :created_at)'
            );
            $sth->bindValue(':identity', $reference->getIdentity()->asString());
            $sth->bindValue(':name', $reference->getName()->asString());
            $sth->bindValue(':url', $reference->getUrl()->asString());
            $sth->bindValue(':created_at', $reference->getCreatedAt()->format(self::DATE_TIME_FORMAT));
            $sth->execute();
        } catch (PDOException $exception) {
            throw new PersistenceException('Failed to save reference.', 0, $exception);
        }
    }
}
