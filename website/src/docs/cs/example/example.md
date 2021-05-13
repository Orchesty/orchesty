---
layout: main.hbs
collection: documentation
name: Example
level: 1 
index: 900
lang: cs

lunr: true
tags: example
menu_exclude: true
---

## Obrázek
![Ukázkový obrázek](/img/stormtroopocat.png "Avatar")

## Tabulka

| Option | Description |
| ------ | ----------- |
| data   | path to data files to supply the data that will be passed into templates. |
| engine | engine to be used for processing templates. Handlebars is the default. |
| ext    | extension to be used for dest files. |


## Odkazy
[link with title](https://hanaboso.com "Hanaboso!")

Autoconverted link https://hanaboso.com

## InfoBlock
``` infoBlock
Nějaký informační text.
```

## WarningBlock
``` warningBlock
Nějaký informační text.
```

## Inline CodeBlock
Sample `inline code` block.

## CodeSample without lang
    awdadad
    adawdawda

## CodeSamples multi-lang
``` CSS 1

button {
    width:20px;
    height:28px;
    color:#fff;
    font-size:28px;
    padding:11px 15px;
    border-radius:5px;
    background:#14ADE5
}
```

``` JS 1

function myFunction() {
    document.write(5 + 6);
}
```

``` jQuery 1

$(document).ready(function{
 jQuery.cssRule(".post", "display", "block");
});
```

``` Ruby 1

[1, 2, 3].each do |n|
    # Prints out a number
    puts "Number #{n}"
end

[1, 2, 3].each {|n| puts "Number #{n}"}
```

``` GO 1

package main
                                                 
import "fmt"
                                                 
func add(x int, y int) int {
return x + y
}
                                                 
func main() {
fmt.Println(add(42, 13))
}
```

## Next CodeSamples

``` Ruby 2

[1, 2, 3].each do |n|
    # Prints out a number
    puts "Number #{n}"
end

[1, 2, 3].each {|n| puts "Number #{n}"}
```

``` GO 2

package main
                                                 
import "fmt"
                                                 
func add(x int, y int) int {
return x + y
}
                                                 
func main() {
fmt.Println(add(42, 13))
}
```