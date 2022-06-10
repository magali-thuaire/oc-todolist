# Welcome to ToDoList App docs contributing guide

Thank you for investing your time in contributing to our project!

In this guide you will get an overview of the contribution workflow from opening an issue, creating a PR, reviewing, and merging the PR.

## New contributor guide

To get an overview of the project, read the [README](README.md). Here are some resources to help you get started with open source contributions:

- [Finding ways to contribute to open source on GitHub](https://docs.github.com/en/get-started/exploring-projects-on-github/finding-ways-to-contribute-to-open-source-on-github)
- [Set up Git](https://docs.github.com/en/get-started/quickstart/set-up-git)
- [GitHub flow](https://docs.github.com/en/get-started/quickstart/github-flow)
- [Collaborating with pull requests](https://docs.github.com/en/github/collaborating-with-pull-requests)

## Create a new issue

If you spot a problem with the docs, [search if an issue already exists](https://docs.github.com/en/github/searching-for-information-on-github/searching-on-github/searching-issues-and-pull-requests#search-by-the-title-body-or-comments).  

If a related issue doesn't exist, you can open a [new issue](https://github.com/magali-thuaire/oc-todolist/issues/new).

## Solve an issue

Scan through our [existing issues](https://github.com/magali-thuaire/oc-todolist/issues) to find one that interests you. You can narrow down the search using `labels` as filters. See [Labels](/contributing/how-to-use-labels.md) for more information. As a general rule, we don’t assign issues to anyone. If you find an issue to work on, you are welcome to open a PR with a fix.

### Create a new branch

Create a branch for your contribution, taking care to name it in a coherent and understandable way (in English preferably).

### Test your changes

**Run the tests**

Verify that they always pass after your changes:

```
symfony run bin/phpunit
```

**Update coverage**

Update the coverage test file, with the following command:

```
symfony run bin/phpunit --coverage-html tests/test-coverage
```

Don't forget to commit this new tests/coverage.xml file!

### Coding conventions

The project follows the good practices of PSR-1 and PSR-12:

[PSR-1](https://www.php-fig.org/psr/psr-1/)  
[PSR-12](https://www.php-fig.org/psr/psr-12/)

The PHP_CS_fixer and PHP_Codesniffer libraries are present in the project to help you to respect good development practices.

**Download Composer libraries**

```
cd tool/php-code-sniffer
composer install
cd tool/php-cs-fixer
composer install
```

**Run PHP_Codesniffer**

```
tools/php-code-sniffer/vendor/bin/phpcs  
tools/php-code-sniffer/vendor/bin/phpcbf
```

**Run PHP_CS_fixer**

```
tool/php-cs-fixer tools/php-cs-fixer/vendor/bin/php-cs-fixer fix
```

### Commit your update

Commit the changes once you are happy with them.

Consider starting the commit message with an applicable emoji:  
🎨 :art: when improving the format/structure of the code  
✨ :sparkles: when adding a new feature
🔧 :wrench: when modifying or adding config files
📝 :memo: when writing docs  
🐞 :lady_beetle: when fixing a bug  
🌀 :cyclone: when refactoring code
🔥 :fire: when removing code or files  
✅ :white_check_mark: when adding tests  
🔒 :lock: when dealing with security  
⬆️ :arrow_up: when upgrading dependencies  
⬇️ :arrow_down: when downgrading dependencies  

Once your changes are ready, don't forget to [self-review](/contributing/self-review.md) to speed up the review process:zap:.

### Create a Pull Request

When you're finished with the changes, push your changes and create a pull request, also known as a PR.

More details about PR on [GitHub documentation](https://docs.github.com/en/pull-requests/collaborating-with-pull-requests/proposing-changes-to-your-work-with-pull-requests/about-pull-requests).

### Your PR is merged!

Congratulations 🎉🎉 The ToDo & Co team thanks you ✨.