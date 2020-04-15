---
layout: main.hbs
collection: documentation
name: Example
level: 1 
index: 100
---

#### Obrázek
![Ukázkový obrázek](/img/stormtroopocat.png "Avatar")

#### Tabulka

| Option | Description |
| ------ | ----------- |
| data   | path to data files to supply the data that will be passed into templates. |
| engine | engine to be used for processing templates. Handlebars is the default. |
| ext    | extension to be used for dest files. |


#### Odkazy
[link with title](https://hanaboso.com "Hanaboso!")

Autoconverted link https://hanaboso.com

#### InfoBlock
``` infoBlock
Nějaký informační text.
```

#### Inline CodeBlock
Sample `inline code` block.

#### CodeBlock without lang
    awdadad
    adawdawda

#### CSS snippet
``` CSS
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

#### JS snippet
``` JS
function myFunction() {
    document.write(5 + 6);
}
```

#### jQuery snippet
``` jQuery
$(document).ready(function{
 jQuery.cssRule(".post", "display", "block");
});
```

#### Ruby snippet
``` Ruby
[1, 2, 3].each do |n|
    # Prints out a number
    puts "Number #{n}"
end

[1, 2, 3].each {|n| puts "Number #{n}"}
```

#### Go snippet
``` GO
package main
                                                 
import "fmt"
                                                 
func add(x int, y int) int {
return x + y
}
                                                 
func main() {
fmt.Println(add(42, 13))
}
```