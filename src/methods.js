
export default {

    async request(url, endpoint, data) {
        let d = new FormData();
        for (const key in data) {
            d.append(key, data[key]);
        }
        let result = await fetch(url + 'api/' + endpoint, {
            method: 'post',
            mode: 'cors',
            body: JSON.stringify(data),
            headers: {
                'Content-Type': 'application/json'
            }
          })
          let json = await result.json();
          return json;
    }

}