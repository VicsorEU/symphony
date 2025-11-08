<?php
namespace App\Command;

use App\Entity\ApiToken;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:make-token', description: 'Create API token')]
class MakeTokenCommand extends Command
{
    public function __construct(private EntityManagerInterface $em) { parent::__construct(); }

    protected function configure(): void
    {
        $this->addArgument('role', InputArgument::REQUIRED, 'root or user')
            ->addArgument('userId', InputArgument::OPTIONAL, 'bind user id for role=user', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $role = $input->getArgument('role');
        $userId = $input->getArgument('userId') ? (int)$input->getArgument('userId') : null;

        if (!in_array($role, ['root','user'], true)) {
            $output->writeln('<error>role must be root or user</error>');
            return Command::FAILURE;
        }
        if ($role==='user' && !$userId) {
            $output->writeln('<comment>role=user without userId: you may bind later after POST</comment>');
        }

        $tokenStr = bin2hex(random_bytes(16));
        $t = (new ApiToken())->setToken($tokenStr)->setRole($role)->setUserId($userId);
        $this->em->persist($t);
        $this->em->flush();

        $output->writeln('TOKEN='.$tokenStr);
        return Command::SUCCESS;
    }
}
