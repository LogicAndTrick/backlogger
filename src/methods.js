
export default {

    async request(url, endpoint, data) {
        let d = new FormData();
        for (const key in data) {
            if (data.hasOwnProperty(key)) d.append(key, data[key]);
        }
        let result = await fetch(url + 'api/' + endpoint, {
            method: 'post',
            mode: 'cors',
            body: d
          })
          let json = await result.json();
          return json;
    }

}