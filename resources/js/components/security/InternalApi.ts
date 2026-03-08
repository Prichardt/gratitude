import axios from 'axios';

const BASE_URL = '/internal-api/application-keys';

export default {
    async getAll() {
        const response = await axios.get(BASE_URL);
        return response.data;
    },
    
    async getOne(id: number | string) {
        const response = await axios.get(`${BASE_URL}/${id}`);
        return response.data;
    },
    
    async store(data: any) {
        const response = await axios.post(BASE_URL, data);
        return response.data;
    },
    
    async update(id: number | string, data: any) {
        const response = await axios.put(`${BASE_URL}/${id}`, data);
        return response.data;
    },
    
    async destroy(id: number | string) {
        const response = await axios.delete(`${BASE_URL}/${id}`);
        return response.data;
    }
};
