import { configureStore } from "@reduxjs/toolkit";
import { loginReducer } from "../features/Login/loginSlice.js";
import { registerReducer } from "../features/Register/registerSlice.js";

export const store = configureStore({
  reducer: {
    login: loginReducer,
    register: registerReducer,
  }
});