import { configureStore } from "@reduxjs/toolkit";
import { loginReducer } from "../Features/Login/loginSlice.js";
import { registerReducer } from "../Features/Register/registerSlice.js";

export const store = configureStore({
  reducer: {
    login: loginReducer,
    register: registerReducer,
  }
});