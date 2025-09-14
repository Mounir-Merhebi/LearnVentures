import axios from "axios";

const API = axios.create({
  baseURL: "http://127.0.0.1:8000/api/v0.1",
});

// Attach token dynamically to avoid using a stale token captured at import time
API.interceptors.request.use((config) => {
  const token = localStorage.getItem("token");
  if (token) {
    config.headers = config.headers || {};
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
}, (error) => Promise.reject(error));

export default API;
