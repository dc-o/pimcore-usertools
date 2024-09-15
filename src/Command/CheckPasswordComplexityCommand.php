<?php
namespace DCO\UserTools\Command;

use DCO\UserTools\Tool\PasswordComplexityTool;
use Pimcore\Console\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CheckPasswordComplexityCommand extends AbstractCommand {
    protected function configure()
    {
        $this
            ->setName('usertools:checkpasswordcomplexity')
            ->setDescription('Checks password complexity of a given password')
            ->addOption('password', 'p', InputOption::VALUE_REQUIRED, 'The password to check');
        ;
    }
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $password = $input->getOption('password');
        $output->writeln('The checked password is: '.$password);
        $ranking = PasswordComplexityTool::rankPassword($password);

        $output->writeln('Length:     ' . $ranking->Length);

        $output->writeln('Lower Case: ' . $ranking->LowercaseCharacters);
        $output->writeln('Upper Case: ' . $ranking->UppercaseCharacters);
        $output->writeln('Numbers:    ' . $ranking->Numbers);
        $output->writeln('Symbols:    ' . $ranking->Symbols);

        $output->writeln('Overall Ranking: '.($ranking->getRanking() * 4) . ' / 4');
        return 0;
    }
}
