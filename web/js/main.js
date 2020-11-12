const BASIC_URL = 'http://localhost';

var alert = {
    container: document.getElementById('ickle.alert'),
    message: document.getElementById('ickle.alert.message'),
    closebtn: document.getElementById('ickle.alert.closebtn'),
}

var form = {
    url: document.getElementById('ickle.form.url'),
    name: document.getElementById('ickle.form.name'),
    submit: document.getElementById('ickle.form.submit')
}

function shortlink(name)
{
    var shortlink = document.createElement('a');
    shortlink.appendChild(document.createTextNode(BASIC_URL + '/' + name));
    shortlink.href = BASIC_URL + '/' + name;

    return shortlink;
}

function submitForm()
{
    fetch(BASIC_URL, {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({ url: form.url.value, name: form.name.value})
    }).then(response => response.json()).then(response => {
        if (response.status === 'success') {
            alert.message.innerHTML = 'Well done! ';
            alert.message.append(shortlink(form.name.value));
            alert.container.className = 'ickle-success';
            alert.container.style.display = "block";
            clearForm();
        } else {
            alert.message.innerText = response.message;
            alert.container.className = 'ickle-error';
            alert.container.style.display = 'block';
        }
    });
}

function clearForm()
{
    form.url.value = '';
    form.name.value = '';
}

form.submit.addEventListener('click', function () { submitForm(); });
alert.closebtn.addEventListener('click', function () { alert.container.style.display = 'none'; });

