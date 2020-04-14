---
layout: main.hbs
collection: documentation
name: Example
level: 1 
index: 100
---
awdad
#### CSS snippet
<pre class='code'><label>CSS</label><code>button {
width:20px;
height:28px;
color:#fff;
font-size:28px;
padding:11px 15px;
border-radius:5px;
background:#14ADE5}
</code></pre>


#### JS snippet
<div markdown="1">
<pre class='code'><label> JS </label><code>&lt;button onclick="myFunction()"&gt;Post&lt;/button&gt;
&lt;script&gt;
function myFunction() {
    document.write(5 + 6);
}
&lt;/script&gt;</code></pre>
<div markdown="1">

#### jQuery snippet
<pre class='code'><label>Jquery</label><code>$(document).ready(function{
 jQuery.cssRule(".post", "display", "block");
});</code></pre>



#### Ruby snippet
<pre class='code'><label>Ruby </label><code>[1, 2, 3].each do |n|
    # Prints out a number
    puts "Number #{n}"
end

[1, 2, 3].each {|n| puts "Number #{n}"}
</code></pre>

#### Go snippet
<pre class='code'><label>Go</label><code>package main
                                                 
import "fmt"
                                                 
func add(x int, y int) int {
return x + y
}
                                                 
func main() {
fmt.Println(add(42, 13))
}
</code></pre>
