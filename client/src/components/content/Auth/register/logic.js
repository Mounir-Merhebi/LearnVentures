import { useNavigate } from "react-router-dom";
import API from "../../../../Services/axios";
import { useSelector, useDispatch } from "react-redux";
import { setField, setName, setEmail, setPassword, setHobbies, setPreferences, setBio, setErrorMessage, clearFields } from "../../../../features/Register/registerSlice";

export const useRegisterForm = () => {
  const navigate = useNavigate();
  const dispatch = useDispatch();
  const { name, email, password, hobbies, preferences, bio, errorMessage } = useSelector((global) => global.register);

  const registerUser = async (e) => {
    e.preventDefault();
    dispatch(setErrorMessage(""));

    try {
      const response = await API.post("/guest/register", {
        name,
        email,
        password,
        hobbies,
        preferences,
        bio
      });

      const token = response.data.payload.token;
      const user = response.data.payload;

      localStorage.setItem("token", token);
      localStorage.setItem("user", JSON.stringify(user));

      dispatch(clearFields());

      navigate("/home");
    } catch (error) {
      if (error.response) {
        dispatch(setErrorMessage(error.response.data.message || "Registration failed"));
      } else {
        dispatch(setErrorMessage("Something went wrong. Please try again."));
      }
    }
  };

    const handleFieldChange = (field, value) => {
      dispatch(setField({ field, value }));
    };

  return {
    name,
    setName,
    email,
    setEmail,
    password,
    setPassword,
    hobbies,
    preferences,
    bio,
    setHobbies,
    setPreferences,
    setBio,
    errorMessage,
    handleFieldChange,
    registerUser,
  };
};
