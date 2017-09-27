PIPES FRAMEWORK BRIDGE

Is a basic platform of which Node consists of.


###How to build PF-bridge:

####On linux:
run the build.sh file
```
$ ./build.sh
```

####On osx:
get the docker machine IP address via ifconfig (e.g. 10.211.55.2)
run the ssh agent forwarder using the ip addres from above
run the build.sh file
```
$ ifconfig
$ ./../frontend/docker/build/ssh-agent-forwarder.sh 10.211.55.2
$ ./build.sh
```