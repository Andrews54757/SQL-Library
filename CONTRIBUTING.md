## Contribution Guidelines

Contributing to SuperSQL is encouraged. We always want to improve.

### Format

Please use the [PEAR format](https://pear.php.net/manual/en/standards.php). Also please refrain from using `[]` to define arrays. Use `array()` instead.

> Note, phpformatter.com can format it too...

### Efficiency

Efficiency is priority with SuperSQL. We recommend you profile your code using [Xdebug](xdebug.org/docs/profiler).

### Size

Size is also very important as we want to be very lightweight. The file size may not grow beyond 30 MB. To add code while staying within the limit, we suggest you look at the rest of the code and try to simplify it. Please note that you may not change variable names for reduced size.

### Ambiguity

Ambiguous code must be avoided. Please try to keep your code clean, neat, and easy to understand.

### Syntax

Syntax must be never changed as a side-effect. This is a recipe for confusion.