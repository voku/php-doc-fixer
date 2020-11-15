
# ***PHP-Documentation-Check***

This is a experiment! Lets check (maybe fix at some point) the php documentation automatically.

```
git clone https://github.com/php/doc-en
cd ..
git clone https://github.com/voku/php-doc-fixer
cd php-doc-fixer
composer install
php bin/phpdocfixer run ../doc-en/reference
```

### Support

For support and donations please visit [Github](https://github.com/voku/simple_html_dom/) | [Issues](https://github.com/voku/simple_html_dom/issues) | [PayPal](https://paypal.me/moelleken) | [Patreon](https://www.patreon.com/voku).

For status updates and release announcements please visit [Releases](https://github.com/voku/simple_html_dom/releases) | [Twitter](https://twitter.com/suckup_de) | [Patreon](https://www.patreon.com/voku/posts).

For professional support please contact [me](https://about.me/voku).

### Thanks

- Thanks to [GitHub](https://github.com) (Microsoft) for hosting the code and a good infrastructure including Issues-Managment, etc.
- Thanks to [IntelliJ](https://www.jetbrains.com) as they make the best IDEs for PHP and they gave me an open source license for PhpStorm!
- Thanks to [Travis CI](https://travis-ci.com/) for being the most awesome, easiest continous integration tool out there!
- Thanks to [StyleCI](https://styleci.io/) for the simple but powerfull code style check.
- Thanks to [PHPStan](https://github.com/phpstan/phpstan) && [Psalm](https://github.com/vimeo/psalm) for really great Static analysis tools and for discover bugs in the code!
