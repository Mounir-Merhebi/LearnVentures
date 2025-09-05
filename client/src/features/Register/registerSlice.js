import { createSlice } from "@reduxjs/toolkit";

const initialState = {
    name: "",
    email: "",
    password: "",
    hobbies: "",
    preferences: "",
    bio: "",
    errorMessage: ""
};

const RegisterSlice = createSlice({
    name: "register",
    initialState,
    reducers: {
        setField: (state, action) => {
            const { field, value } = action.payload;
            state[field] = value;
        },
        setName: (state, action) => {
            state.name = action.payload;
        },
        setEmail: (state, action) => {
            state.email = action.payload;
        },
        setPassword: (state, action) => {
            state.password = action.payload;
        },
        setHobbies: (state, action) => {
            state.hobbies = action.payload;
        },
        setPreferences: (state, action) => {
            state.preferences = action.payload;
        },
        setBio: (state, action) => {
            state.bio = action.payload;
        },
        setErrorMessage: (state, action) => {
            state.errorMessage = action.payload;
        },
        clearFields: (state) => {
            state.name = "";
            state.email = "";
            state.password = "";
            state.hobbies = "";
            state.preferences = "";
            state.bio = "";
            state.errorMessage = "";
        }
    }
});

export const {
    setField,
    setName,
    setEmail,
    setPassword,
    setHobbies,
    setPreferences,
    setBio,
    setErrorMessage,
    clearFields
} = RegisterSlice.actions;

export const registerReducer = RegisterSlice.reducer;