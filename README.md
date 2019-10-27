# DeezerAlert

![GitHub contributors](https://img.shields.io/github/contributors/thomasfagot/DeezerAlert)
![GitHub license](https://img.shields.io/github/license/thomasfagot/DeezerAlert)
![GitHub stars](https://img.shields.io/github/stars/thomasfagot/DeezerAlert?style=social)
![GitHub forks](https://img.shields.io/github/forks/thomasfagot/DeezerAlert?style=social)

DeezerAlert is a tool that allows Deezer users to receive an email notification when their favorite artists release new content.
This is a quick-and-dirty personal project 

## Prerequisities

Before you begin, ensure you have met the following requirements:
* You have PHP 7.2 or higher.
* You have a public account on Deezer (as OAuth identification is not yet implemented).
* SMTP is enabled on your server.

## Installing DeezerAlert

To install DeezerAlert, follow these steps:
* Clone the project.
* Fill the `.env` file. You can find the ID of your Deezer profile by going [here](https://www.deezer.com/profile/me) and copy the number in the URL.

## Using DeezerAlert

```
php index.php
```

## Contributing

Pull requests are welcome. To contribute, follow these steps:

1. Fork this repository.
2. Create a branch: `git checkout -b <branch_name>`. 
3. Make your changes and commit them: `git commit -m '<commit_message>'`
4. Push to the original branch: `git push origin <project_name>/<location>`
5. Create the pull request.

Alternatively see the GitHub documentation on [creating a pull request](https://help.github.com/en/github/collaborating-with-issues-and-pull-requests/creating-a-pull-request).

## License 

This project uses the following license: [MIT](LICENSE).
