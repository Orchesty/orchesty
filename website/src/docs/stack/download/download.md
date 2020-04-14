---
name: Download
collection: documentation
layout: main.hbs
level: 1
index: 10
---

 <form action="{#var portal_api_url}/installer" method="post" target="_blank">
            <div class="col-lg-12">
                <h4>Installer</h4>
            </div>
            <div>
                <div class="col-lg-12">
                    <h4>Choose Logs</h4>
                    <div class="col-lg-6">
                    <label>
                        <input type="radio" class="with-gap" name="logs" value="elasticsearch" checked/> 
                        <span>Elasticsearch</span>
                    </label>    
                    </div>
                    <div class="col-lg-6">
                    <label>
                        <input type="radio" class="with-gap" name="logs" value="logstash">
                        <span>Logstash</span>
                    </label>    
                    </div>
                </div>
                <div class="col-lg-12">
                    <h4>Choose metrics</h4>
                    <div class="col-lg-6">
                    <label>
                        <input type="radio" class="with-gap" name="metrics" value="influxdb" checked> 
                        <span>Influx</span>
                    </label>    
                    </div>
                    <div class="col-lg-6">
                    <label>
                        <input type="radio" class="with-gap" name="metrics" value="mongo"> 
                        <span>Mongo</span>
                    </label>
                    </div>
                </div>
                <div class="col-lg-12">
                    <h4>Choose database</h4>
                    <div class="col-lg-6">
                    <label>
                        <input id="database_input" type="checkbox" name="database" value="true" checked/>
                       <span>Database</span>
                    </label>    
                    </div>
                </div>
            </div>
            <div class="col-lg-12">
                <button class="btn btn-success mt-3" type="submit">Get Installer file</button>
            </div>
        </form>
