# PHPhotos

**Project Description**

This is a mostly ChatGPT generated PHP photo gallery application.

## Table of Contents

- [Installation](#installation)
- [Usage](#usage)
- [Contributing](#contributing)
- [License](#license)

## Installation

1. Clone this repository to your local machine:
```shell
git clone https://github.com/svandragt/phphotos.git
cd phphotos
php -S localhost:5000 -t src   
```

Put pngs in src/photos/. Thumbs will be cached in src/cache/, so that needs to be writable by the PHP user.

It requires PHP 8.1 and the GD extension.
