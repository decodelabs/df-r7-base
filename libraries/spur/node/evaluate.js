var input = '';

process.stdin.on('data', function(chunk) {
    input += chunk;
});

process.stdin.on('end', function() {
    input = JSON.parse(input);
    var func;
    eval('func = function(data) {'+input.js+'};');

    var output = {
        result: func(input.data)
    };

    process.stdout.write(JSON.stringify(output));
});