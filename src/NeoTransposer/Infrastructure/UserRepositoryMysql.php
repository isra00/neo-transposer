<?php

namespace NeoTransposer\Infrastructure;

use NeoTransposer\Domain\Entity\User;
use NeoTransposer\Domain\Repository\FeedbackRepository;
use NeoTransposer\Domain\Repository\UserRepository;
use NeoTransposer\Domain\ValueObject\NotesRange;

final class UserRepositoryMysql extends MysqlRepository implements UserRepository
{
    public function __construct(
        protected FeedbackRepository $userPerformanceRepository)
    {
        parent::__construct();
    }

    public function readFromId(int $idUser): ?User
    {
        return $this->readFromField('id_user', $idUser);
    }

    public function readFromEmail(string $email): ?User
    {
        return $this->readFromField('email', $email);
    }

    private function readFromField($field, $fieldValue): ?User
    {
        if (!in_array($field, ['email', 'id_user'])) {
            throw new \InvalidArgumentException('Only email and id_user are accepted');
        }

        // Exact match only: LIKE would let a caller pass "%" or "_" wildcards and
        // match an arbitrary user's row (login is passwordless, so that is a takeover).
        $sql = "SELECT * FROM user WHERE $field = ?";

        $ret = null;

        if ($userdata = $this->dbConnection->select($sql, [$fieldValue])) {
            $userdata = (array) $userdata[0];
            $userPerformance = $this->userPerformanceRepository->readUserPerformance($userdata['id_user']);

            $ret = new User(
                $userdata['email'],
                $userdata['id_user'],
                new NotesRange($userdata['lowest_note'], $userdata['highest_note']),
                $userdata['id_book'],
                $userdata['wizard_step1'],
                $userdata['wizard_lowest_attempts'],
                $userdata['wizard_highest_attempts'],
                $userPerformance
            );
        }

        return $ret;
    }

    /**
     * Create or update the user in the database.
     *
     * @param  User  $user  The User object to persist.
     * @return int The user ID, if it was not set.
     */
    public function save(User $user): int
    {
        if ($user->id_user) {
            return $this->dbConnection->table('user')
                ->where('id_user', (int) $user->id_user)
                ->update([
                    'lowest_note'	=> $user->range->lowest ?? null,
                    'highest_note'	=> $user->range->highest ?? null,
                    'id_book'		=> $user->id_book,
                    'wizard_step1' 	=> $user->wizard_step1,
                    'wizard_lowest_attempts' => $user->wizard_lowest_attempts,
                    'wizard_highest_attempts' => $user->wizard_highest_attempts,
                ]);
        }

        return $user->id_user = (int) $this->dbConnection->table('user')->insertGetId([
            'email'			=> $user->email,
            'lowest_note'	=> $user->range->lowest ?? null,
            'highest_note'	=> $user->range->highest ?? null,
            'id_book'		=> $user->id_book,
            'register_ip'	=> $user->registerIp,
        ]);
    }

    /**
     * Update the user logging the voice range change
     *
     * @param  User  $user  The User object to persist.
     * @param  string  $method  Either 'wizard' or 'manual'.
     */
    public function saveWithVoiceChange(User $user, string $method): void
    {
        if (empty($user->id_user)) {
            throw new \InvalidArgumentException('The user must have an ID');
        }

        if (!in_array($method, [User::METHOD_WIZARD, User::METHOD_MANUAL, User::METHOD_UNHAPPY])) {
            throw new \InvalidArgumentException("Invalid voice range update method '$method'");
        }

        $this->dbConnection->transaction(function () use ($user, $method) {
            // If user had NULL voice, don't record the change
            $currentVoiceRange = $this->dbConnection->select('SELECT lowest_note FROM user WHERE id_user = ?', [$user->id_user]);
            $currentVoiceRange = (array) $currentVoiceRange[0];

            if (!empty($currentVoiceRange['lowest_note'])) {
                $this->dbConnection->table('log_voice_range')->insert([
                    'id_user'		=> $user->id_user,
                    'method'		=> $method,
                    'lowest_note'	=> $user->range->lowest,
                    'highest_note'	=> $user->range->highest,
                ]);
            }

            $this->save($user);
        });
    }

    public function readIpFromUsersWithNullCountry(): array
    {
        return array_map(
            fn ($row) => (array) $row,
            $this->dbConnection->select('SELECT register_ip FROM user WHERE country IS NULL')
        );
    }

    public function saveUserCountryByIp(string $countryIsoCode, string $ip): void
    {
        $this->dbConnection->table('user')->where('register_ip', $ip)->update(['country' => $countryIsoCode]);
    }

    public function readVoiceRangeFromAllUsers(): array
    {
        return array_map(
            fn ($row) => (array) $row,
            $this->dbConnection->select('SELECT id_user, email, lowest_note, highest_note FROM user')
        );
    }
}
