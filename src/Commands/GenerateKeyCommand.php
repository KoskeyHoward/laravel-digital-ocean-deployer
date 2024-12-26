<?php

namespace Koskey\LaravelDigitalOceanDeployer\Commands;

use Illuminate\Console\Command;

class GenerateKeyCommand extends Command
{
    protected $signature = 'deployer:generate-key {--path= : Path to your SSH private key}';
    protected $description = 'Generate base64 encoded SSH key for GitHub Actions';

    public function handle()
    {
        $defaultPath = $_SERVER['HOME'] . '/.ssh/id_rsa';
        $keyPath = $this->option('path') ?? $defaultPath;

        if (!file_exists($keyPath)) {
            $this->error("SSH key not found at: $keyPath");
            if ($this->confirm('Would you like to generate a new SSH key?')) {
                $this->generateSSHKey();
                return;
            }
            return 1;
        }

        $privateKey = file_get_contents($keyPath);
        $base64Key = base64_encode($privateKey);

        $this->info('Your base64 encoded private key:');
        $this->line($base64Key);
        $this->info("\nAdd this as DO_SSH_KEY in your GitHub repository secrets");
        
        // Show the public key for convenience
        $publicKeyPath = $keyPath . '.pub';
        if (file_exists($publicKeyPath)) {
            $this->info("\nYour public key (add this to your DigitalOcean server's authorized_keys):");
            $this->line(file_get_contents($publicKeyPath));
        }
    }

    protected function generateSSHKey()
    {
        $this->info('Generating new SSH key pair...');
        $email = $this->ask('Enter your email for the SSH key');
        
        $command = sprintf('ssh-keygen -t rsa -b 4096 -C "%s" -f ~/.ssh/id_rsa -N ""', $email);
        exec($command, $output, $returnCode);

        if ($returnCode === 0) {
            $this->info('SSH key pair generated successfully!');
            $this->handle(); // Re-run to show the encoded key
        } else {
            $this->error('Failed to generate SSH key pair');
            return 1;
        }
    }
}
