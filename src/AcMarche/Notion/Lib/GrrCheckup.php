<?php

namespace AcMarche\Notion\Lib;

class GrrCheckup
{
    private ?\PDO $pdoGrr = null;

    public function connect()
    {
        $this->pdoGrr = new \PDO(
            'mysql:host=localhost;dbname=grr_ville', $_ENV['GRR_DB_USER'], $_ENV['GRR_DB_PASSWORD'],
        );
        $this->pdoGrr->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    /**
     * @throws \Exception
     */
    public function execute(): array
    {
        $data = [];
        foreach ($this->findAll() as $entry) {
            if ($entry['repeat_id']) {
                $results = $this->findRepeatByEntry($entry['repeat_id']);
                if (count($results) === 0) {
                    $data[] = $entry;
                }
            }
        }

        return $data;
    }

    public function findRepeatByEntry(int $id): array
    {
        $this->connect();
        $sql = "SELECT * FROM grr_repeat WHERE id = :id";
        $statement = $this->pdoGrr->prepare($sql);
        $statement->bindParam(':id', $id, \PDO::PARAM_INT);

        try {
            $statement->execute();

            return $statement->fetchAll();
        } catch (\Exception $exception) {
            throw new \Exception(
                json_encode($data).' '.json_encode($statement->errorInfo()).' '.$exception->getMessage(),
            );
        }
    }

    public function findAll(): array
    {
        $this->connect();
        $sql = "SELECT * FROM grr_entry";
        $statement = $this->pdoGrr->prepare($sql);

        try {
            $statement->execute();

            return $statement->fetchAll();
        } catch (\Exception $exception) {
            throw new \Exception(
                json_encode($data).' '.json_encode($statement->errorInfo()).' '.$exception->getMessage(),
            );
        }
    }

}