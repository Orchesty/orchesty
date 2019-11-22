---
layout: main.hbs
name: Example
collection: example
level: 1
---

#### CSS snippet
<pre class='code code-css'><label>CSS</label><code>button {
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
<pre class='code code-javascript'><label> JS </label><code>&lt;button onclick="myFunction()"&gt;Post&lt;/button&gt;
&lt;script&gt;
function myFunction() {
    document.write(5 + 6);
}
&lt;/script&gt;</code></pre>
<div markdown="1">

#### jQuery snippet
<pre class='code code-jquery'><label>Jquery</label><code>$(document).ready(function{
 jQuery.cssRule(".post", "display", "block");
});</code></pre>



#### ruby snippet
<pre class='code code-ruby'><label> ruby </label><code>[1, 2, 3].each do |n|
    # Prints out a number
    puts "Number #{n}"
end

[1, 2, 3].each {|n| puts "Number #{n}"}
</code></pre>

#### Go snippet
<pre class='code code-go'><label>Go</label><code>package main
                                                 
import "fmt"
                                                 
func add(x int, y int) int {
return x + y
}
                                                 
func main() {
fmt.Println(add(42, 13))
}
</code></pre>
