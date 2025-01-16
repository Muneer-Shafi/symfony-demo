{ pkgs ? import (fetchTarball "https://github.com/NixOS/nixpkgs/archive/nixos-24.11.tar.gz") {} }:

pkgs.mkShell {
  name = "symfony-env";

  # Dependencies for Symfony and Ollama
  buildInputs = [
    pkgs.php                      # Latest PHP version in NixOS 24.11
    pkgs.phpPackages.composer     # Composer for dependency management
    pkgs.symfony-cli              # Symfony CLI
    pkgs.nodejs                   # Node.js for frontend dependencies
    pkgs.yarn                     # Yarn (optional, replace with npm if preferred)
    pkgs.redis                    # Redis (optional, if used in your app)
    pkgs.openssl                  # Required for secure HTTPS connections
    pkgs.zlib                     # Compression library
    pkgs.libpng                   # Image processing
    pkgs.curl                     # HTTP requests (required for Ollama installation)
    pkgs.zip                      # ZIP support for PHP
    pkgs.unzip                    # Unzipping dependencies
    pkgs.bash                     # Bash shell
    pkgs.ollama                   # Ensure Ollama is available in the environment
  ];

  # Environment variables and shell hooks
  shellHook = ''
    # Install Ollama if not already installed (Only needed the first time)
    if ! command -v ollama &> /dev/null; then
      echo "Ollama not found, installing..."
      curl https://ollama.ai/install.sh | sh
    fi

    # Pull the llama2 model (or any other model) if not already pulled
    if ! ollama pull llama2 &> /dev/null; then
      echo "Pulling the llama2 model..."
      ollama pull llama2
    fi

    # Start the Ollama server (only if it's not already running)
    if ! pgrep -x "ollama" > /dev/null; then
      echo "Starting Ollama server..."
      ollama serve &
    fi

    # Ensure necessary directories are writable and set up
    chmod -R u+w ./vendor ./composer.lock 2>/dev/null || true
    mkdir -p ./vendor

    # Run Composer install if composer.json exists
    if [ -f composer.json ]; then
      echo "composer.json detected. Installing dependencies..."
      composer install || true
    fi

    echo "Symfony environment and Ollama setup complete. You can now start your Symfony server and use Ollama."
  '';
}
