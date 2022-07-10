#Модуль создания и подключения модулей для elenyum

Проекта <a href='https://github.com/elenyum-ru/elenyum'>elenyum</a>

[<img alt="N|Solid" height="100" src="https://images.assetsdelivery.com/compings_v2/arbuzu/arbuzu1606/arbuzu160600041.jpg"/>](https://github.com/elenyum-ru/elenyum)


Подключите модуль перейдя в папку module
- git clone https://github.com/elenyum-ru/Maker.git

Для создания модуля используйте команду:
- php bin\console module:create moduleName
- moduleName - название вашего модуля

Для подключения нового модуля:
- php bin\console module:install moduleName --token=youGitHubToken
- moduleName - название вашего модуля
- --token - gitHub токен (подробнее тут: https://github.com/settings/tokens)


