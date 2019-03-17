
import cookie from 'js-cookie';

export default {

    async request(url, endpoint, data) {
        let d = new FormData();
        if (data instanceof FormData) {
            d = data;
        } else {
            for (const key in data) {
                d.append(key, data[key]);
            }
        }
        let result = await fetch(url + 'api/' + endpoint, {
            method: 'post',
            mode: 'cors',
            body: JSON.stringify(data),
            headers: {
                'Content-Type': 'application/json',
                'X-Password': cookie.get('password')
            }
        });
        let json = await result.json();
        return json;
    },

    async upload(url, endpoint, data) {
        let d = new FormData();
        if (data instanceof FormData) {
            d = data;
        } else {
            for (const key in data) {
                d.append(key, data[key]);
            }
        }
        let result = await fetch(url + 'api/' + endpoint, {
            method: 'post',
            mode: 'cors',
            body: data,
            headers: {
                'X-Password': cookie.get('password')
            }
        });
        let json = await result.json();
        return json;
    }

}