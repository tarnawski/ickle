const BASIC_URL = 'http://localhost';

const send = (url, name) =>
    fetch(BASIC_URL, {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({ url: url, name: name})
    }).then(response => response.json());

var toast = {
    container: new bootstrap.Toast(document.getElementById('ickle.toast')),
    message: document.getElementById('ickle.toast.message')
};
var form = {
    url: document.getElementById('ickle.url'),
    name: document.getElementById('ickle.name')
}
var alert = {
    container: document.getElementById('ickle.alert'),
    shortling: document.getElementById('ickle.alert.shortlink')
}

function clearForm()
{
    form.url.value = '';
    form.name.value = '';
}

function shortlink(name)
{
    var shortlink = document.createElement('a');
    shortlink.appendChild(document.createTextNode(BASIC_URL + '/' + name));
    shortlink.href = BASIC_URL + '/' + name;

    return shortlink;
}

document.getElementById('ickle.create').addEventListener('click', function () {
    send(form.url.value, form.name.value).then(response => {
        if (response.status === 'success') {
            toast.container.hide();
            alert.shortling.innerHTML = '';
            alert.shortling.append(shortlink(form.name.value));
            alert.container.classList.add("show");
            clearForm();

            return;
        }
        if (response.status === 'error') {
            toast.message.innerText = response.message;
            toast.container.show();

            return;
        }
    });
});
