<?php

namespace AcMarche\Notion\Lib;

use Carbon\Carbon;

class Grr
{
    private ?\PDO $pdo = null;
    private int $bktype = 0;
    public static array $rooms = [
        113 => 'La Box',
        114 => 'La Créative',
        115 => 'Meeting Room',
        116 => 'Relax Room',
        117 => 'La Digital Room',
        135 => 'L\'Arène',
        136 => 'Talentum',
        137 => 'Cusine',
        138 => 'L\'Inspirante',
    ];

    public function connect()
    {
        $this->pdo = new \PDO('mysql:host=localhost;dbname=grr_ville', $_ENV['GRR_DB_USER'], $_ENV['GRR_DB_PASSWORD']);
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    public function findByRoomId(int $roomId): array
    {
        if (!$roomId) {
            $ids = array_keys(Grr::$rooms);
        } else {
            $ids = [$roomId];
        }

        $sql = "SELECT id,from_unixtime(start_time, '%Y-%m-%d') as dayStart,from_unixtime(end_time, '%Y-%m-%d') as dayEnd,from_unixtime(start_time, '%H:%i') as dayStartHours,from_unixtime(end_time, '%H:%i') as dayEndHours,start_time,end_time,entry_type,repeat_id,room_id,timestamp,type,statut_entry,moderate,supprimer 
FROM grr_entry 
WHERE room_id IN (".implode(",", $ids).") AND (moderate = :approuved OR moderate = 2) AND supprimer = :del";

        $statement = $this->pdo->prepare($sql);
        $toApprouved = $del = 0;
        $statement->bindParam(':approuved', $toApprouved, \PDO::PARAM_INT);
        $statement->bindParam(':del', $del, \PDO::PARAM_INT);

        try {
            $statement->execute();

            return $statement->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $exception) {
            Mailer::sendError("grr esquare findByRoomId ".$exception->getMessage());
            throw new \Exception(
                json_encode($data).' '.json_encode($statement->errorInfo()).' '.$exception->getMessage(),
            );
        }
    }

    /**
     * @throws \Exception
     */
    private function insertEntry(array $data): void
    {
        $sql = 'INSERT INTO grr_entry(`start_time`,`end_time`,`entry_type`,`repeat_id`,`room_id`,`timestamp`,`create_by`,`beneficiaire_ext`,`beneficiaire`,`name`,`type`,`description`,`statut_entry`,`option_reservation`,`moderate`)
VALUES(:start_time,:end_time,:entry_type,:repeat_id,:room_id,:timestamp,:create_by,:beneficiaire_ext,:beneficiaire,:name,:type,:description,:statut_entry,:option_reservation,:moderate)';

        $statement = $this->pdo->prepare($sql);

        try {
            $statement->execute($data);
        } catch (\Exception $exception) {
            Mailer::sendError("grr esquare error insert entry ".$exception->getMessage());
            throw new \Exception(
                json_encode($data).' '.json_encode($statement->errorInfo()).' '.$exception->getMessage(),
            );
        }
    }

    /**
     * @throws \Exception
     */
    private function insertUser(array $user, string $email): void
    {
        if ($this->checkUser($email)) {
            return;
        }

        $sql = 'INSERT INTO grr_utilisateurs(`login`,`nom`,`prenom`,`password`,`email`,`statut`,`etat`,`default_area`,`default_style`,`default_list_type`,`source`)
VALUES(:login,:nom,:prenom,:password,:email,:statut,:etat,:default_area,:default_style,:default_list_type,:source)';

        $statement = $this->pdo->prepare($sql);

        try {
            $statement->execute($user);
        } catch (\Exception $exception) {
            Mailer::sendError("grr esquare error insert user ".$exception->getMessage());
            throw new \Exception(
                json_encode($user).' '.json_encode($statement->errorInfo()).' '.$exception->getMessage(),
            );
        }
    }

    /**
     * @throws \Exception
     */
    public function checkUser(string $email): bool
    {
        $sql = 'SELECT * FROM grr_utilisateurs WHERE `email` =:email';

        $statement = $this->pdo->prepare($sql);

        try {
            $statement->execute(['email' => $email]);
            $result = $statement->fetchAll();
            if (count($result) > 0) {
                return true;
            }
        } catch (\Exception $exception) {
            Mailer::sendError("grr esquare check user ".$exception->getMessage());
            throw new \Exception(
                json_encode($user).' '.json_encode($statement->errorInfo()).' '.$exception->getMessage(),
            );
        }

        return false;
    }

    /**
     * @throws \Exception
     */
    public function checkFree(int $roomId, \DateTimeInterface $startTime, \DateTimeInterface $endTime)
    {
        $startModif = Carbon::createFromInterface($startTime);
        $startModif->modify('+1 second');
        $endModif = Carbon::createFromInterface($endTime);
        $endModif->modify('-1 second');

        $sql = "SELECT * FROM grr_entry WHERE (`start_time` BETWEEN :start AND :end OR `end_time` BETWEEN :start AND :end) AND `room_id` = :room_id";
        $stmt = $this->pdo->prepare($sql);

        try {
            $stmt->execute([
                ':start' => $startModif->getTimestamp(),
                ':end' => $endModif->getTimestamp(),
                ':room_id' => $roomId,
            ]);

            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $exception) {
            Mailer::sendError("grr esquare check free ".$exception->getMessage());
            throw new \Exception(
                json_encode($stmt->errorInfo()).' '.$exception->getMessage(),
            );
        }
    }

}