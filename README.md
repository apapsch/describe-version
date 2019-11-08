# Describe Git version in a constant

This Composer plugin uses your working directory as Git repository
and creates the file `version.php` defining the constant `VERSION`
with the Git version.

You can use the file how you see fit, i.e. via autoloader.

```
{
    "autoload": {
        "files": [
            "version.php"
        ]
    }
}
```

# Usage

The version string is available in the constant:

```
printf("Using %s\n", VERSION);
```
